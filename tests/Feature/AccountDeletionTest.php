<?php

namespace Tests\Feature;

use App\Models\AcademyClass;
use App\Models\AcademyParent;
use App\Models\AccountDeletionRequest;
use App\Models\ActivityLog;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\Course;
use App\Models\Role;
use App\Models\SecureFile;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherLessonCheckIn;
use App\Models\TeacherStudentNote;
use App\Models\User;
use App\Services\AccountIdentityService;
use App\Services\SecureFiles\SecureFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    private AccountIdentityService $identity;

    private SecureFileService $files;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->identity = $this->app->make(AccountIdentityService::class);
        $this->files = $this->app->make(SecureFileService::class);
        RateLimiter::clear('privacy-deletion|127.0.0.1');
    }

    public function test_public_privacy_and_deletion_pages_do_not_require_login(): void
    {
        $this->withSession(['locale' => 'it'])
            ->get('/privacy')
            ->assertOk()
            ->assertSee('Accademia Ucraina Ballet')
            ->assertSee('Informativa sulla privacy')
            ->assertSee('data-deletion')
            ->assertSee('non è ancora inserito')
            ->assertDontSee('Whoops')
            ->assertDontSee('fully GDPR');

        $this->get('/privacy?lang=en')->assertRedirect('/privacy');
        $this->get('/privacy')
            ->assertOk()
            ->assertSee('Privacy policy');

        $this->withSession(['locale' => 'it'])
            ->get('/data-deletion')
            ->assertOk()
            ->assertSee('Accademia Ucraina Ballet')
            ->assertSee('AUB')
            ->assertSee('Email dell’account')
            ->assertDontSee('Whoops');
    }

    public function test_public_form_validates_and_does_not_reveal_whether_the_account_exists(): void
    {
        $student = $this->makeStudent('Anna', 'Rossi', ['birth_date' => '2014-03-02']);
        $user = $this->link($student, 'anna.privacy@example.test');

        $this->from('/data-deletion')
            ->post('/data-deletion', ['email' => 'not-an-email', 'role' => 'student'])
            ->assertRedirect('/data-deletion')
            ->assertSessionHasErrors(['email', 'confirm', 'privacy']);
        $this->assertSame(0, AccountDeletionRequest::query()->count());

        $known = $this->from('/data-deletion')->post('/data-deletion', $this->form($user->email));
        $unknown = $this->from('/data-deletion')->post('/data-deletion', $this->form('nobody@example.test'));

        $known->assertRedirect('/data-deletion')->assertSessionHas('deletion_received', true);
        $unknown->assertRedirect('/data-deletion')->assertSessionHas('deletion_received', true);
        $this->assertStringNotContainsString('Anna', (string) $known->getContent());
        $this->assertStringNotContainsString('2014-03-02', (string) $unknown->getContent());
        $this->assertStringNotContainsString((string) $user->id, (string) $known->getContent());

        $this->assertSame(2, AccountDeletionRequest::query()->count());
        $this->assertSame($user->id, AccountDeletionRequest::query()->where('email', $user->email)->value('user_id'));
        $this->assertNull(AccountDeletionRequest::query()->where('email', 'nobody@example.test')->value('user_id'));
        $this->assertSame(0, ActivityLog::query()->count());
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_honeypot_and_rate_limit_do_not_store_or_leak(): void
    {
        $this->from('/data-deletion')
            ->post('/data-deletion', $this->form('bot@example.test', ['website' => 'https://spam.test']))
            ->assertRedirect('/data-deletion')
            ->assertSessionHas('deletion_received', true);
        $this->assertSame(0, AccountDeletionRequest::query()->count());

        for ($i = 0; $i < 5; $i++) {
            $this->post('/data-deletion', $this->form('person'.$i.'@example.test'))->assertRedirect();
        }

        $this->post('/data-deletion', $this->form('blocked@example.test'))->assertStatus(429);
        $this->assertNull(AccountDeletionRequest::query()->where('email', 'blocked@example.test')->first());
    }

    public function test_app_request_uses_only_the_authenticated_actor_and_revokes_tokens(): void
    {
        $student = $this->makeStudent('Nora', 'Conti');
        $user = $this->link($student, 'nora.privacy@example.test');
        $other = $this->link($this->makeStudent('Other', 'Child'), 'other.privacy@example.test');
        $token = $user->createToken('phone', ['mobile'])->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/account/deletion-request', [
                'password' => 'wrong-password',
                'confirm' => true,
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.fields.password.0', __('privacy.deletion_password_invalid'));
        $this->assertSame(0, AccountDeletionRequest::query()->count());

        $this->withToken($token)
            ->postJson('/api/v1/account/deletion-request', [
                'password' => 'password',
                'confirm' => true,
                'user_id' => $other->id,
                'email' => $other->email,
                'message' => 'close mine',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonMissing(['email' => $other->email]);

        $row = AccountDeletionRequest::query()->firstOrFail();
        $this->assertSame($user->id, $row->user_id);
        $this->assertSame('nora.privacy@example.test', $row->email);
        $this->assertSame(AccountDeletionRequest::SOURCE_APP, $row->source);
        $this->assertSame(0, $user->tokens()->count());
        $this->assertTrue($user->fresh()->is_active);
        $this->assertSame('Nora', $student->fresh()->first_name);
        $this->assertTrue($other->fresh()->is_active);

        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_staff_cannot_use_the_app_deletion_endpoint(): void
    {
        $admin = $this->makeStaffAdmin();
        $token = $admin->createToken('phone', ['mobile'])->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/account/deletion-request', [
                'password' => 'password',
                'confirm' => true,
            ])
            ->assertUnauthorized();
    }

    public function test_parent_completion_does_not_delete_the_child_or_child_files(): void
    {
        $admin = $this->makeStaffAdmin();
        $student = $this->makeStudent('Lucia', 'Greco', ['birth_date' => '2015-01-09']);
        $parent = AcademyParent::query()->create([
            'first_name' => 'Maria',
            'last_name' => 'Greco',
            'email' => 'maria.greco@example.test',
            'phone' => '333000111',
            'tax_code' => 'GRCMRA80A01H501U',
        ]);
        $student->parents()->attach($parent->id, ['relation_type' => 'mother']);
        $parentUser = $this->link($parent, 'maria.greco@example.test');
        $medical = $this->files->store($student, 'medical_document', $this->jpegUpload('cert.jpg'), null);
        $parentPhoto = $this->files->store($parent, 'profile_photo', $this->jpegUpload('parent.jpg'), null);
        $chat = Chat::query()->create(['user_id' => $parentUser->id, 'type' => Chat::TYPE_ACADEMY]);
        ChatMessage::query()->create([
            'chat_id' => $chat->id,
            'author_id' => $parentUser->id,
            'body' => 'private parent text',
        ]);

        $deletion = AccountDeletionRequest::query()->create([
            'user_id' => $parentUser->id,
            'email' => $parentUser->email,
            'account_type' => 'parent',
            'source' => 'web',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post('/settings/privacy/'.$deletion->id.'/complete', [
                'confirm' => 1,
                'resolution_note' => 'reviewed',
            ])
            ->assertRedirect();

        $this->assertSame('Lucia', $student->fresh()->first_name);
        $this->assertSame('2015-01-09', $student->fresh()->birth_date?->toDateString() ?? (string) $student->fresh()->birth_date);
        $this->assertTrue($student->parents()->where('parents.id', $parent->id)->exists());
        $this->assertNotNull(SecureFile::query()->find($medical->id));
        $this->assertNull(SecureFile::withTrashed()->find($parentPhoto->id));
        $parent->refresh();
        $this->assertSame('Removed', $parent->first_name);
        $this->assertNull($parent->phone);
        $this->assertSame('[removed]', ChatMessage::query()->first()->body);
        $this->assertNotNull(Chat::query()->find($chat->id));
        $this->assertFalse($parentUser->fresh()->is_active);
        $this->assertSame(0, $parentUser->tokens()->count());
    }

    public function test_student_completion_keeps_the_educational_file_and_removes_login_and_photo(): void
    {
        $admin = $this->makeStaffAdmin();
        $student = $this->makeStudent('Elena', 'Bianchi', [
            'email' => 'elena.card@example.test',
            'birth_date' => '2013-04-04',
        ]);
        $user = $this->link($student, 'elena.app@example.test');
        $user->createToken('phone', ['mobile']);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => 'reset-token',
            'created_at' => now(),
        ]);
        $course = Course::query()->create(['discipline' => 'academy', 'name' => 'Classico', 'sort_order' => 1]);
        $class = AcademyClass::query()->create([
            'course_id' => $course->id,
            'name' => 'Gruppo A',
            'color' => '#1A2B44',
            'sort_order' => 1,
        ]);
        $class->students()->attach($student->id);
        $photo = $this->files->store($student, 'profile_photo', $this->jpegUpload('face.jpg'), null);
        $medical = $this->files->store($student, 'medical_document', $this->jpegUpload('cert.jpg'), null);

        $deletion = AccountDeletionRequest::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'account_type' => 'student',
            'source' => 'app',
            'status' => 'verified',
            'requested_at' => now(),
            'message' => 'please erase my login',
        ]);

        $this->actingAs($admin)
            ->get('/settings/privacy')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Privacy/DeletionRequests')
                ->where('requests.0.email', 'elena.app@example.test')
                ->missing('requests.0.birth_date'))
            ->assertDontSee('2013-04-04');

        $this->actingAs($admin)
            ->post('/settings/privacy/'.$deletion->id.'/complete', ['confirm' => 1])
            ->assertRedirect();

        $student->refresh();
        $this->assertSame('Elena', $student->first_name);
        $this->assertSame('elena.card@example.test', $student->email);
        $this->assertTrue($class->students()->where('students.id', $student->id)->exists());
        $this->assertNull(SecureFile::withTrashed()->find($photo->id));
        $this->assertNotNull(SecureFile::query()->find($medical->id));
        $user->refresh();
        $this->assertFalse($user->is_active);
        $this->assertStringEndsWith('@account.invalid', $user->email);
        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame(0, DB::table('password_reset_tokens')->where('email', 'elena.app@example.test')->count());
        $this->assertNotNull(ActivityLog::query()->where('action', 'account_deletion.completed')->first());
        $this->assertStringNotContainsString(
            'please erase',
            (string) json_encode(ActivityLog::query()->where('action', 'account_deletion.completed')->value('properties')),
        );

        $this->postJson('/api/v1/auth/login', [
            'email' => 'elena.app@example.test',
            'password' => 'password',
            'device_name' => 'phone',
        ])->assertUnauthorized();
    }

    public function test_teacher_completion_keeps_lessons_and_clears_private_notes_and_location(): void
    {
        $admin = $this->makeStaffAdmin();
        $teacher = Teacher::query()->create([
            'type' => 'permanent',
            'first_name' => 'Luca',
            'last_name' => 'Neri',
            'name' => 'Luca Neri',
            'email' => 'luca.neri@example.test',
            'phone' => '333222111',
        ]);
        $user = $this->link($teacher, 'luca.neri@example.test');
        $student = $this->makeStudent('Sofia', 'Ricci');
        TeacherStudentNote::query()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'body' => 'only this teacher',
        ]);
        $lesson = $this->makeLesson($teacher);
        TeacherLessonCheckIn::query()->create([
            'scheduled_lesson_id' => $lesson->id,
            'teacher_id' => $teacher->id,
            'user_id' => $user->id,
            'checked_in_at' => now(),
            'latitude' => 41.8900000,
            'longitude' => 12.4900000,
            'distance_meters' => 3,
            'radius_meters' => 50,
            'device_name' => 'Pixel',
        ]);

        $deletion = AccountDeletionRequest::query()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'account_type' => 'teacher',
            'source' => 'app',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post('/settings/privacy/'.$deletion->id.'/complete', ['confirm' => 1])
            ->assertRedirect();

        $this->assertSame($teacher->id, $lesson->fresh()->teacher_id);
        $this->assertSame(0, TeacherStudentNote::query()->count());
        $checkIn = TeacherLessonCheckIn::query()->firstOrFail();
        $this->assertNull($checkIn->latitude);
        $this->assertNull($checkIn->device_name);
        $this->assertNotNull($checkIn->checked_in_at);
        $teacher->refresh();
        $this->assertSame('Removed', $teacher->first_name);
        $this->assertFalse($user->fresh()->is_active);
        $this->assertSame('Sofia', $student->fresh()->first_name);
    }

    public function test_admin_routes_require_an_administrator_and_completion_requires_delete_permission(): void
    {
        $this->get('/settings/privacy')->assertRedirect('/');

        $staff = User::factory()->create([
            'role_id' => Role::query()->where('slug', 'administrator')->firstOrFail()->id,
            'account_type' => User::TYPE_STAFF,
            'can_delete' => false,
            'can_write' => true,
            'password' => 'password',
        ]);
        $deletion = AccountDeletionRequest::query()->create([
            'email' => 'pending@example.test',
            'account_type' => 'other',
            'source' => 'web',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->actingAs($staff)
            ->post('/settings/privacy/'.$deletion->id.'/complete', ['confirm' => 1])
            ->assertForbidden();
        $this->assertSame('pending', $deletion->fresh()->status);

        $reader = User::factory()->create([
            'account_type' => User::TYPE_STAFF,
            'role_id' => null,
            'can_delete' => true,
            'password' => 'password',
        ]);
        $this->actingAs($reader)->get('/settings/privacy')->assertForbidden();
    }

    public function test_completion_without_confirmation_does_not_run(): void
    {
        $admin = $this->makeStaffAdmin();
        $deletion = AccountDeletionRequest::query()->create([
            'email' => 'wait@example.test',
            'account_type' => 'other',
            'source' => 'web',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from('/settings/privacy')
            ->post('/settings/privacy/'.$deletion->id.'/complete', [])
            ->assertSessionHasErrors('confirm');
        $this->assertSame('pending', $deletion->fresh()->status);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function form(string $email, array $extra = []): array
    {
        return [
            'email' => $email,
            'role' => 'student',
            'message' => 'please review',
            'confirm' => '1',
            'privacy' => '1',
            ...$extra,
        ];
    }

    private function link(Student|AcademyParent|Teacher $profile, string $email): User
    {
        return $this->identity->createAndLink($profile, [
            'name' => $profile->name ?? trim(($profile->first_name ?? '').' '.($profile->last_name ?? '')),
            'email' => $email,
            'password' => 'password',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeStudent(string $firstName, string $lastName, array $overrides = []): Student
    {
        return Student::query()->create([
            'name' => trim($firstName.' '.$lastName),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'status' => 'active',
            ...$overrides,
        ]);
    }

    private function makeStaffAdmin(): User
    {
        return User::factory()->create([
            'name' => 'Administrator',
            'role_id' => Role::query()->where('slug', 'administrator')->firstOrFail()->id,
            'account_type' => User::TYPE_STAFF,
            'is_active' => true,
            'can_write' => true,
            'can_delete' => true,
            'password' => 'password',
        ]);
    }

    private function makeLesson(Teacher $teacher): \App\Models\ScheduledLesson
    {
        $building = \App\Models\AcademyBuilding::query()->firstOrFail();
        $room = \App\Models\AcademyRoom::query()->where('academy_building_id', $building->id)->firstOrFail();
        $course = Course::query()->create(['discipline' => 'academy', 'name' => 'Docenti', 'sort_order' => 2]);
        $class = AcademyClass::query()->create([
            'course_id' => $course->id,
            'name' => 'Sala',
            'color' => '#1A2B44',
            'sort_order' => 2,
        ]);
        $week = \App\Models\ScheduleWeek::query()->create([
            'week_start_date' => '2026-09-07',
            'week_end_date' => '2026-09-13',
            'title' => 'Settimana',
            'status' => \App\Models\ScheduleWeek::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $subject = \App\Models\Lesson::query()->create([
            'discipline' => 'ballet',
            'name' => 'Classico',
            'duration_minutes' => 90,
        ]);

        return \App\Models\ScheduledLesson::query()->create([
            'schedule_week_id' => $week->id,
            'academy_building_id' => $building->id,
            'academy_room_id' => $room->id,
            'academy_class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'lesson_id' => $subject->id,
            'lesson_date' => '2026-09-08',
            'starts_at' => '10:00:00',
            'ends_at' => '11:30:00',
            'title' => 'Lezione',
            'status' => \App\Models\ScheduledLesson::STATUS_PUBLISHED,
        ]);
    }

    private function jpegUpload(string $name): UploadedFile
    {
        $image = imagecreatetruecolor(8, 8);
        ob_start();
        imagejpeg($image);
        $bytes = ob_get_clean();
        imagedestroy($image);
        $path = tempnam(sys_get_temp_dir(), 'aubpriv');
        file_put_contents($path, $bytes);

        return new UploadedFile($path, $name, 'image/jpeg', null, true);
    }
}
