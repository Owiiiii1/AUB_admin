<?php

namespace Tests\Feature;

use App\Models\AcademyClass;
use App\Models\AcademyParent;
use App\Models\ActivityLog;
use App\Models\ClassLesson;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\SecureFile;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AccountIdentityService;
use App\Services\SecureFiles\SecureFileService;
use App\Support\PublicStorageArchitectureGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecureFilesTest extends TestCase
{
    use RefreshDatabase;

    private AccountIdentityService $identity;

    private SecureFileService $files;

    protected function setUp(): void
    {
        parent::setUp();
        $this->identity = $this->app->make(AccountIdentityService::class);
        $this->files = $this->app->make(SecureFileService::class);
    }

    public function test_upload_never_lands_on_public_disk_and_path_is_opaque(): void
    {
        $student = $this->makeStudent('Mario', 'Rossi');
        $file = $this->files->store($student, 'profile_photo', $this->jpegUpload('Mario-Rossi.jpg'), null);

        $this->assertSame('aub_private', $file->disk);
        $this->assertSame(64, strlen($file->sha256));
        $this->assertStringStartsWith('objects/', $file->path);
        $this->assertStringNotContainsString('Mario', $file->path);
        $this->assertStringNotContainsString((string) $student->id, $file->path);
        $this->assertStringNotContainsString('students', $file->path);
        $this->assertFalse(Storage::disk('public')->exists($file->path));
        $this->assertTrue(Storage::disk('aub_private')->exists($file->path));
        $this->assertSame($file->sha256, hash('sha256', Storage::disk('aub_private')->get($file->path)));
        $this->assertNotSame(hash('sha256', $this->jpegBytes()), $file->sha256);
        $this->assertNotNull($file->thumbnail());
    }

    public function test_fake_jpg_php_svg_and_oversized_are_rejected(): void
    {
        $student = $this->makeStudent('Anna', 'Verdi');

        foreach ([
            $this->namedUpload('photo.jpg', 'this is not an image'),
            $this->namedUpload('photo.jpg', '<?php phpinfo();'),
            $this->namedUpload('photo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            $this->namedUpload('huge.jpg', str_repeat('x', 5 * 1024 * 1024 + 10)),
        ] as $upload) {
            try {
                $this->files->store($student, 'profile_photo', $upload, null);
                $this->fail('Expected rejection for '.$upload->getClientOriginalName());
            } catch (\App\Exceptions\SecureFileException) {
                $this->assertTrue(true);
            }
        }

        $this->assertSame(0, SecureFile::query()->count());
    }

    public function test_permitted_pdf_and_jpeg_are_accepted(): void
    {
        $student = $this->makeStudent('Luca', 'Neri');
        $photo = $this->files->store($student, 'profile_photo', $this->jpegUpload('face.jpg'), null);
        $pdf = $this->files->store($student, 'identity_document', $this->pdfUpload('passaporto.pdf'), null);

        $this->assertSame('image/jpeg', $photo->mime_type);
        $this->assertSame('application/pdf', $pdf->mime_type);
        $this->assertSame('passaporto.pdf', $pdf->original_name);
    }

    public function test_unknown_category_is_denied(): void
    {
        $this->expectException(\App\Exceptions\SecureFileException::class);
        $this->files->storeBinary($this->makeStudent('X', 'Y'), 'not_registered', $this->jpegBytes(), 'a.jpg');
    }

    public function test_admin_can_view_file_and_path_is_not_in_payload(): void
    {
        $admin = $this->makeStaffAdmin();
        $student = $this->makeStudent('Mario', 'Rossi');
        $file = $this->files->store($student, 'profile_photo', $this->jpegUpload('face.jpg'), $admin);

        $this->actingAs($admin)
            ->get('/secure-files/'.$file->uuid)
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringNotContainsString($file->path, json_encode($student->fresh()->profilePhoto()?->publicMetadata()));
        $this->assertArrayNotHasKey('disk', $file->publicMetadata());
        $this->assertArrayNotHasKey('sha256', $file->publicMetadata());
    }

    public function test_authorization_matrix_and_enumeration_are_404(): void
    {
        $own = $this->makeStudent('Giulia', 'Verdi');
        $other = $this->makeStudent('Marco', 'Neri');
        $photo = $this->files->store($own, 'profile_photo', $this->jpegUpload('face.jpg'), null);
        $idDoc = $this->files->store($own, 'identity_document', $this->pdfUpload('id.pdf'), null);

        $parentUser = $this->linkParentTo($own, 'parent-own@example.test');
        $strangerParent = $this->linkParentTo($other, 'parent-other@example.test');
        $studentUser = $this->linkStudent($own, 'student-own@example.test');
        $otherStudentUser = $this->linkStudent($other, 'student-other@example.test');
        $teacher = $this->makeTeacher('Elena', 'Bianchi');
        $teacherUser = $this->identity->createAndLink($teacher, [
            'name' => 'Elena Bianchi',
            'email' => 'teacher-files@example.test',
            'password' => 'password',
        ]);
        $this->assignTeacherToStudent($teacher, $own);

        $unknown = (string) Str::uuid();

        $this->assertApiFile($parentUser, $photo, 200);
        $this->assertApiFile($parentUser, $idDoc, 404);
        $this->assertApiFile($strangerParent, $photo, 404);
        $this->assertApiFile($studentUser, $photo, 200);
        $this->assertApiFile($otherStudentUser, $photo, 404);
        $this->assertApiFile($teacherUser, $photo, 200);
        $this->assertApiFile($teacherUser, $idDoc, 404);

        $ownToken = $this->loginToken($studentUser);
        $this->withToken($ownToken)->get('/api/v1/files/'.$unknown)->assertNotFound();
        $this->withToken($ownToken)->get('/api/v1/files/'.$photo->uuid)
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->getJson('/api/v1/files/'.$photo->uuid)->assertUnauthorized();

        $inactive = $this->linkStudent($this->makeStudent('Idle', 'User'), 'inactive-files@example.test');
        $inactive->update(['is_active' => false]);
        $this->assertFalse(app(\App\Services\SecureFiles\FileAccessService::class)->allows(
            $inactive->fresh(),
            $photo,
            'view',
        ));
    }

    public function test_sensitive_view_and_download_are_audited_but_profile_photo_view_is_not(): void
    {
        $admin = $this->makeStaffAdmin();
        $student = $this->makeStudent('Mario', 'Rossi');
        $photo = $this->files->store($student, 'profile_photo', $this->jpegUpload('face.jpg'), $admin);
        $doc = $this->files->store($student, 'identity_document', $this->pdfUpload('id.pdf'), $admin);

        $this->actingAs($admin)->get('/secure-files/'.$photo->uuid)->assertOk();
        $this->actingAs($admin)->get('/secure-files/'.$doc->uuid)->assertOk();
        $this->actingAs($admin)->get('/secure-files/'.$doc->uuid.'/download')->assertOk();

        $this->assertSame(0, ActivityLog::query()->where('action', 'secure_file.viewed')->where('subject_label', $photo->uuid)->count());
        $this->assertSame(1, ActivityLog::query()->where('action', 'secure_file.viewed')->where('subject_label', $doc->uuid)->count());
        $this->assertSame(1, ActivityLog::query()->where('action', 'secure_file.downloaded')->where('subject_label', $doc->uuid)->count());
        $this->assertSame(1, ActivityLog::query()->where('action', 'secure_file.uploaded')->where('subject_label', $doc->uuid)->count());
    }

    public function test_replace_keeps_old_file_until_new_is_valid(): void
    {
        $student = $this->makeStudent('Mario', 'Rossi');
        $first = $this->files->store($student, 'profile_photo', $this->jpegUpload('one.jpg'), null);

        try {
            $this->files->replace($student, 'profile_photo', $this->namedUpload('photo.jpg', 'not-an-image'), null);
            $this->fail('Invalid replace must fail');
        } catch (\App\Exceptions\SecureFileException) {
            $this->assertTrue(true);
        }

        $this->assertTrue(SecureFile::query()->whereKey($first->id)->exists());
        $this->assertTrue(Storage::disk('aub_private')->exists($first->path));

        $second = $this->files->replace($student, 'profile_photo', $this->jpegUpload('two.jpg'), null);
        $this->assertNotSame($first->id, $second->id);
        $this->assertSoftDeleted('secure_files', ['id' => $first->id]);
        $this->assertSame($second->id, $student->fresh()->profilePhoto()?->id);
        $this->assertSame(1, ActivityLog::query()->where('action', 'secure_file.replaced')->count());
    }

    public function test_migration_dry_run_is_idempotent_and_switches_relation(): void
    {
        $student = $this->makeStudent('Mario', 'Rossi');
        $bytes = $this->jpegBytes();
        $legacy = 'students/'.$student->id.'/documents/face.jpg';
        Storage::disk('public')->put($legacy, $bytes);
        $student->forceFill(['student_photo_path' => $legacy])->save();

        Artisan::call('aub:secure-files:migrate', ['--dry-run' => true]);
        $this->assertSame(0, SecureFile::query()->count());
        $this->assertTrue(Storage::disk('public')->exists($legacy));
        $this->assertNull($student->fresh()->profilePhoto());

        Artisan::call('aub:secure-files:migrate');
        $file = $student->fresh()->profilePhoto();
        $this->assertNotNull($file);
        $this->assertSame(64, strlen((string) $file->sha256));
        $this->assertFalse(Storage::disk('public')->exists($legacy));
        $this->assertTrue(Storage::disk('aub_legacy_quarantine')->exists($legacy));
        $this->assertNull($student->fresh()->student_photo_path);
        $this->assertStringStartsWith('objects/', $file->path);

        $count = SecureFile::query()->where('variant', 'original')->count();
        Artisan::call('aub:secure-files:migrate');
        $this->assertSame($count, SecureFile::query()->where('variant', 'original')->count());
    }

    public function test_missing_legacy_source_is_reported_without_write(): void
    {
        $student = $this->makeStudent('Ghost', 'File');
        $student->forceFill(['student_photo_path' => 'students/999/missing.jpg'])->save();

        $code = Artisan::call('aub:secure-files:migrate');
        $this->assertSame(0, $code);
        $this->assertSame(0, SecureFile::query()->count());
        $this->assertStringContainsString('Missing', Artisan::output());
    }

    public function test_me_photo_url_is_authenticated_and_omits_storage_path(): void
    {
        $student = $this->makeStudent('Mario', 'Rossi');
        $file = $this->files->store($student, 'profile_photo', $this->jpegUpload('face.jpg'), null);
        $user = $this->linkStudent($student, 'mario.photo@example.test');

        $payload = $this->withToken($this->loginToken($user = $user))
            ->getJson('/api/v1/me')
            ->assertOk()
            ->json('data.profile');

        $this->assertSame(url('/api/v1/files/'.$file->uuid), $payload['photo_url']);
        $this->assertSame($file->uuid, $payload['photo']['file_uuid']);
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('/storage/', $json);
        $this->assertStringNotContainsString($file->path, $json);
        $this->assertStringNotContainsString($file->sha256, $json);
    }

    public function test_architecture_guard_passes_application_and_detects_fixture(): void
    {
        $guard = new PublicStorageArchitectureGuard;
        $this->assertSame([], $guard->scan(base_path()));

        $hits = $guard->scan(base_path(), ['tests/Fixtures/Architecture'], []);
        $this->assertNotEmpty($hits);
        $this->assertTrue(collect($hits)->contains(fn (array $hit): bool => str_contains($hit['snippet'], '/storage/')));
    }

    private function assertApiFile(User $user, SecureFile $file, int $status): void
    {
        $this->withToken($this->loginToken($user))
            ->get('/api/v1/files/'.$file->uuid)
            ->assertStatus($status);
    }

    private function jpegUpload(string $name): UploadedFile
    {
        return $this->namedUpload($name, $this->jpegBytes());
    }

    private function pdfUpload(string $name): UploadedFile
    {
        return $this->namedUpload($name, $this->pdfBytes());
    }

    private function namedUpload(string $name, string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'aubup');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, null, null, true);
    }

    private function jpegBytes(): string
    {
        $image = imagecreatetruecolor(24, 24);
        imagefilledrectangle($image, 0, 0, 23, 23, imagecolorallocate($image, 20, 90, 160));
        ob_start();
        imagejpeg($image, null, 90);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    private function pdfBytes(): string
    {
        return "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 10 10]/Parent 2 0 R>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
    }

    private function makeStudent(string $first, string $last): Student
    {
        return Student::query()->create([
            'name' => trim($first.' '.$last),
            'first_name' => $first,
            'last_name' => $last,
            'status' => 'active',
        ]);
    }

    private function makeTeacher(string $first, string $last): Teacher
    {
        return Teacher::query()->create([
            'type' => 'permanent',
            'first_name' => $first,
            'last_name' => $last,
            'name' => trim($first.' '.$last),
        ]);
    }

    private function makeStaffAdmin(): User
    {
        $role = Role::query()->where('slug', 'administrator')->firstOrFail();

        return User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin-files@example.test',
            'role_id' => $role->id,
            'account_type' => User::TYPE_STAFF,
            'is_active' => true,
            'can_write' => true,
            'can_delete' => true,
            'password' => 'password',
        ]);
    }

    private function linkStudent(Student $student, string $email): User
    {
        return $this->identity->createAndLink($student, [
            'name' => $student->displayName(),
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function linkParentTo(Student $student, string $email): User
    {
        $parent = AcademyParent::query()->create([
            'first_name' => 'Parent',
            'last_name' => $student->last_name,
            'email' => $email,
        ]);
        $parent->students()->attach($student->id, ['relation_type' => 'mother']);

        return $this->identity->createAndLink($parent, [
            'name' => $parent->displayName(),
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function assignTeacherToStudent(Teacher $teacher, Student $student): void
    {
        $course = Course::query()->create([
            'discipline' => 'academy',
            'name' => 'File Test Course',
            'sort_order' => 99,
        ]);
        $class = AcademyClass::query()->create([
            'course_id' => $course->id,
            'name' => 'File Class',
            'color' => '#1A2B44',
            'sort_order' => 1,
        ]);
        $class->students()->attach($student->id);
        $year = \App\Models\AcademicYear::query()->first() ?? \App\Models\AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-06-30',
            'is_active' => true,
        ]);
        $lesson = Lesson::query()->create([
            'discipline' => 'ballet',
            'name' => 'Classico',
            'duration_minutes' => 90,
        ]);
        $classLesson = ClassLesson::query()->create([
            'academic_year_id' => $year->id,
            'academy_class_id' => $class->id,
            'lesson_id' => $lesson->id,
            'hours' => 1,
            'sort_order' => 1,
        ]);
        $classLesson->teachers()->attach($teacher->id, ['hours' => 1]);
    }

    private function loginToken(User $user): string
    {
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Owl Files',
        ])->assertOk()->json('data.token');

        $this->assertIsString($token);

        return $token;
    }
}
