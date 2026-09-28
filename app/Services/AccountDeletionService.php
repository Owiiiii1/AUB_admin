<?php

namespace App\Services;

use App\Models\AccountDeletionRequest;
use App\Models\AcademyParent;
use App\Models\ChatMessage;
use App\Models\SecureFile;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherLessonCheckIn;
use App\Models\TeacherStudentNote;
use App\Models\User;
use App\Services\SecureFiles\SecureFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountDeletionService
{
    public function __construct(
        private readonly SecureFileService $files,
        private readonly ActivityLogger $activity,
    ) {}

    /**
     * @return array{remove: list<array{key: string, detail: string}>, retain: list<array{key: string, detail: string}>}
     */
    public function planFor(?User $user, string $claimedRole): array
    {
        $type = $user?->account_type ?? $claimedRole;

        return match ($type) {
            User::TYPE_STUDENT => $this->studentPlan(),
            User::TYPE_PARENT => $this->parentPlan(),
            User::TYPE_TEACHER => $this->teacherPlan(),
            User::TYPE_STAFF => $this->staffPlan(),
            default => [
                'remove' => [],
                'retain' => [[
                    'key' => 'none',
                    'detail' => 'No linked account. Nothing is deleted until a matching account is confirmed.',
                ]],
            ],
        };
    }

    public function submitPublic(string $email, string $role, ?string $message): AccountDeletionRequest
    {
        $email = Str::lower(trim($email));
        $existing = AccountDeletionRequest::query()
            ->where('email', $email)
            ->whereIn('status', AccountDeletionRequest::OPEN_STATUSES)
            ->first();

        if ($existing instanceof AccountDeletionRequest) {
            return $existing;
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        return AccountDeletionRequest::query()->create([
            'user_id' => $user?->id,
            'email' => $email,
            'account_type' => $role,
            'source' => AccountDeletionRequest::SOURCE_WEB,
            'status' => AccountDeletionRequest::STATUS_PENDING,
            'message' => $this->blankToNull($message),
            'requested_at' => now(),
        ]);
    }

    public function submitFromApp(User $user, string $password, ?string $message): AccountDeletionRequest
    {
        if (! $user->isMobileActor()) {
            throw ValidationException::withMessages([
                'account' => __('privacy.deletion_app_not_available'),
            ]);
        }

        if (! Hash::check($password, (string) $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('privacy.deletion_password_invalid'),
            ]);
        }

        $request = AccountDeletionRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', AccountDeletionRequest::OPEN_STATUSES)
            ->first();

        if (! $request instanceof AccountDeletionRequest) {
            $request = AccountDeletionRequest::query()->create([
                'user_id' => $user->id,
                'email' => Str::lower((string) $user->email),
                'account_type' => (string) $user->account_type,
                'source' => AccountDeletionRequest::SOURCE_APP,
                'status' => AccountDeletionRequest::STATUS_PENDING,
                'message' => $this->blankToNull($message),
                'requested_at' => now(),
            ]);
        }

        $user->tokens()->delete();

        return $request;
    }

    public function markVerified(AccountDeletionRequest $deletion, User $admin, Request $http): void
    {
        if (! $deletion->isOpen() || $deletion->status === AccountDeletionRequest::STATUS_VERIFIED) {
            return;
        }

        $deletion->forceFill([
            'status' => AccountDeletionRequest::STATUS_VERIFIED,
            'verified_at' => now(),
        ])->save();

        $this->activity->log(
            $http,
            'account_deletion.verified',
            'account_deletion_request',
            $deletion->id,
            'Deletion request '.$deletion->id,
        );
    }

    public function reject(AccountDeletionRequest $deletion, User $admin, Request $http, ?string $note): void
    {
        if (! $deletion->isOpen()) {
            return;
        }

        $deletion->forceFill([
            'status' => AccountDeletionRequest::STATUS_REJECTED,
            'resolution_note' => $this->blankToNull($note),
            'processed_at' => now(),
            'processed_by' => $admin->id,
        ])->save();

        $this->activity->log(
            $http,
            'account_deletion.rejected',
            'account_deletion_request',
            $deletion->id,
            'Deletion request '.$deletion->id,
        );
    }

    public function complete(AccountDeletionRequest $deletion, User $admin, Request $http, ?string $note): void
    {
        if (! $deletion->isOpen()) {
            return;
        }

        DB::transaction(function () use ($deletion, $admin, $http, $note): void {
            $deletion->forceFill([
                'status' => AccountDeletionRequest::STATUS_PROCESSING,
            ])->save();

            $user = $deletion->user;
            if ($user instanceof User) {
                $this->apply($user, $admin, $http);
            }

            $deletion->forceFill([
                'status' => AccountDeletionRequest::STATUS_COMPLETED,
                'verified_at' => $deletion->verified_at ?? now(),
                'processed_at' => now(),
                'processed_by' => $admin->id,
                'resolution_note' => $this->blankToNull($note),
            ])->save();

            $this->activity->log(
                $http,
                'account_deletion.completed',
                'account_deletion_request',
                $deletion->id,
                'Deletion request '.$deletion->id,
                null,
                [
                    'linked_user' => $user?->id,
                    'account_type' => $user?->account_type ?? $deletion->account_type,
                ],
            );
        });
    }

    private function apply(User $user, User $admin, Request $http): void
    {
        match ($user->account_type) {
            User::TYPE_PARENT => $this->applyParent($user, $admin, $http),
            User::TYPE_TEACHER => $this->applyTeacher($user, $admin, $http),
            User::TYPE_STUDENT => $this->applyStudent($user, $admin, $http),
            default => null,
        };

        $this->anonymizeMessages($user, $admin, $http);
        $this->closeLogin($user);
    }

    private function applyStudent(User $user, User $admin, Request $http): void
    {
        $profile = $user->studentProfile;
        if ($profile instanceof Student) {
            $this->purgeCategory($profile, 'profile_photo', $admin, $http);
        }
    }

    private function applyParent(User $user, User $admin, Request $http): void
    {
        $parent = $user->parentProfile;
        if (! $parent instanceof AcademyParent) {
            return;
        }

        $this->purgeCategory($parent, 'profile_photo', $admin, $http);
        $this->purgeCategory($parent, 'identity_document', $admin, $http);

        $parent->forceFill([
            'first_name' => 'Removed',
            'last_name' => 'guardian '.$parent->id,
            'email' => 'deleted-parent-'.$parent->id.'@account.invalid',
            'phone' => null,
            'tax_code' => null,
            'notes' => null,
        ])->save();
    }

    private function applyTeacher(User $user, User $admin, Request $http): void
    {
        $teacher = $user->teacherProfile;
        if (! $teacher instanceof Teacher) {
            return;
        }

        $this->purgeCategory($teacher, 'profile_photo', $admin, $http);

        TeacherStudentNote::query()->where('teacher_id', $teacher->id)->delete();

        TeacherLessonCheckIn::query()
            ->where('teacher_id', $teacher->id)
            ->update([
                'latitude' => null,
                'longitude' => null,
                'accuracy_meters' => null,
                'distance_meters' => null,
                'device_name' => null,
            ]);

        $teacher->forceFill([
            'first_name' => 'Removed',
            'last_name' => 'teacher '.$teacher->id,
            'name' => 'Removed teacher '.$teacher->id,
            'email' => 'deleted-teacher-'.$teacher->id.'@account.invalid',
            'phone' => null,
            'tax_code' => null,
            'description' => null,
        ])->save();
    }

    private function anonymizeMessages(User $user, User $admin, Request $http): void
    {
        ChatMessage::withTrashed()
            ->where('author_id', $user->id)
            ->orderBy('id')
            ->each(function (ChatMessage $message) use ($admin, $http): void {
                foreach ($message->secureFiles()->get() as $file) {
                    $this->purgeFile($file, $admin, $http);
                }
                $message->forceFill([
                    'body' => '[removed]',
                ])->save();
            });
    }

    private function purgeCategory(Student|AcademyParent|Teacher $owner, string $category, User $admin, Request $http): void
    {
        $file = $owner->secureFile($category);
        if ($file === null) {
            return;
        }

        $this->purgeFile($file, $admin, $http);
    }

    private function purgeFile(SecureFile $file, User $admin, Request $http): void
    {
        $this->files->delete($file, $admin, $http);
        $trashed = SecureFile::withTrashed()->find($file->id);
        if ($trashed instanceof SecureFile) {
            $this->files->purge($trashed);
        }
    }

    private function closeLogin(User $user): void
    {
        $user->tokens()->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        $user->forceFill([
            'name' => 'Removed account '.$user->id,
            'email' => 'deleted-user-'.$user->id.'@account.invalid',
            'password' => Hash::make(Str::random(40)),
            'admin_visible_password' => null,
            'remember_token' => null,
            'must_set_password' => false,
            'is_active' => false,
        ])->save();
    }

    /**
     * @return array{remove: list<array{key: string, detail: string}>, retain: list<array{key: string, detail: string}>}
     */
    private function studentPlan(): array
    {
        return [
            'remove' => [
                ['key' => 'login', 'detail' => 'Login, password, sessions and API tokens are removed. The person cannot sign in.'],
                ['key' => 'chat', 'detail' => 'Message text and files sent by this account are replaced or removed. The thread itself stays.'],
                ['key' => 'profile_photo', 'detail' => 'The student profile photo file is removed.'],
            ],
            'retain' => [
                ['key' => 'student_record', 'detail' => 'The student card stays, including name, birth date, address, tax code and contacts. The academy has not defined a retention period for the educational file.'],
                ['key' => 'enrollment', 'detail' => 'Class and course links stay so the timetable and history still refer to a student.'],
                ['key' => 'attendance', 'detail' => 'Attendance marks stay. They are the class record, not only the login.'],
                ['key' => 'documents', 'detail' => 'Uploaded documents and consent scans stay on the student. A parent request does not remove them either.'],
                ['key' => 'medical', 'detail' => 'The medical certificate file and its review status stay on the student.'],
                ['key' => 'teacher_notes', 'detail' => 'Notes other teachers wrote about this student stay. They are not deleted with the student login.'],
            ],
        ];
    }

    /**
     * @return array{remove: list<array{key: string, detail: string}>, retain: list<array{key: string, detail: string}>}
     */
    private function parentPlan(): array
    {
        return [
            'remove' => [
                ['key' => 'login', 'detail' => 'Login, password, sessions and API tokens are removed.'],
                ['key' => 'parent_identity', 'detail' => 'The guardian name, email, phone, tax code and notes on the parent card are replaced.'],
                ['key' => 'parent_files', 'detail' => 'The guardian profile photo and identity-document file, when stored on the parent, are removed.'],
                ['key' => 'chat', 'detail' => 'Message text and files sent by this account are replaced or removed.'],
            ],
            'retain' => [
                ['key' => 'child', 'detail' => 'Linked students are not deleted and are not unlinked. The child record remains.'],
                ['key' => 'child_files', 'detail' => 'Documents and medical certificates uploaded for a child stay on the student.'],
                ['key' => 'notices', 'detail' => 'Notice read or acknowledgement rows stay as the send history.'],
            ],
        ];
    }

    /**
     * @return array{remove: list<array{key: string, detail: string}>, retain: list<array{key: string, detail: string}>}
     */
    private function teacherPlan(): array
    {
        return [
            'remove' => [
                ['key' => 'login', 'detail' => 'Login, password, sessions and API tokens are removed.'],
                ['key' => 'teacher_identity', 'detail' => 'The teacher name, email, phone, tax code and description are replaced.'],
                ['key' => 'profile_photo', 'detail' => 'The teacher profile photo is removed.'],
                ['key' => 'notes', 'detail' => 'Private notes this teacher wrote about students are deleted.'],
                ['key' => 'check_in_location', 'detail' => 'GPS coordinates and the device name on this teacher’s lesson check-ins are cleared.'],
                ['key' => 'chat', 'detail' => 'Message text and files sent by this account are replaced or removed.'],
            ],
            'retain' => [
                ['key' => 'lessons', 'detail' => 'Lessons, classes and the timetable stay. The teacher row remains so those records still have a teacher.'],
                ['key' => 'attendance', 'detail' => 'Attendance marks this teacher saved stay on the lessons.'],
                ['key' => 'check_in_fact', 'detail' => 'The fact that a check-in existed stays, without the location.'],
                ['key' => 'certificates', 'detail' => 'Certificate files stored on the teacher, if any, stay. They are not the profile photo.'],
            ],
        ];
    }

    /**
     * @return array{remove: list<array{key: string, detail: string}>, retain: list<array{key: string, detail: string}>}
     */
    private function staffPlan(): array
    {
        return [
            'remove' => [
                ['key' => 'login', 'detail' => 'The staff login is disabled and the password, email and name on the user are replaced. There is no self-service deletion in the app.'],
            ],
            'retain' => [
                ['key' => 'activity', 'detail' => 'Activity log rows stay. They record administrative actions.'],
                ['key' => 'operations', 'detail' => 'Students, lessons and other academy records this person worked on are not deleted.'],
            ],
        ];
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
