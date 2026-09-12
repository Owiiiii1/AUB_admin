<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AcademyClass;
use App\Models\AcademyParent;
use App\Models\Course;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AccountIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiFoundationTest extends TestCase
{
    use RefreshDatabase;

    private AccountIdentityService $identity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->identity = $this->app->make(AccountIdentityService::class);
    }

    public function test_health_returns_ok_contract(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'data' => [
                    'status' => 'ok',
                    'api_version' => 'v1',
                ],
            ]);
    }

    public function test_student_parent_and_teacher_can_login(): void
    {
        foreach (['student', 'parent', 'teacher'] as $type) {
            $actor = $this->makeLinkedActor($type, $type.'@example.test');

            $response = $this->postJson('/api/v1/auth/login', [
                'email' => $actor->email,
                'password' => 'password',
                'device_name' => 'Owl '.$type,
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.token_type', 'Bearer')
                ->assertJsonStructure(['data' => ['token', 'token_type', 'expires_at']]);

            $this->assertNotEmpty($response->json('data.token'));
            $this->assertNotEmpty($response->json('data.expires_at'));
        }
    }

    public function test_login_failures_share_invalid_credentials_envelope(): void
    {
        $student = $this->makeLinkedActor('student', 'anna@example.test');

        $badPassword = $this->postJson('/api/v1/auth/login', [
            'email' => $student->email,
            'password' => 'wrong-password',
            'device_name' => 'Owl iPhone',
        ]);

        $unknown = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.test',
            'password' => 'password',
            'device_name' => 'Owl iPhone',
        ]);

        $inactive = $this->makeLinkedActor('student', 'inactive@example.test', active: false);
        $inactiveLogin = $this->postJson('/api/v1/auth/login', [
            'email' => $inactive->email,
            'password' => 'password',
            'device_name' => 'Owl iPhone',
        ]);

        $staff = $this->makeStaffAdmin();
        $staffLogin = $this->postJson('/api/v1/auth/login', [
            'email' => $staff->email,
            'password' => 'password',
            'device_name' => 'Owl iPhone',
        ]);

        $orphan = User::factory()->student()->create([
            'email' => 'orphan@example.test',
            'password' => 'password',
        ]);
        $orphanLogin = $this->postJson('/api/v1/auth/login', [
            'email' => $orphan->email,
            'password' => 'password',
            'device_name' => 'Owl iPhone',
        ]);

        foreach ([$badPassword, $unknown, $inactiveLogin, $staffLogin, $orphanLogin] as $response) {
            $response->assertUnauthorized()->assertExactJson([
                'success' => false,
                'error' => [
                    'code' => 'invalid_credentials',
                    'message' => 'Invalid credentials.',
                ],
            ]);
        }

        $this->assertSame(0, PersonalAccessToken::query()->count());
    }

    public function test_missing_device_name_is_validation_error(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'anna@example.test',
            'password' => 'password',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonPath('error.message', 'The given data was invalid.')
            ->assertJsonStructure(['error' => ['fields' => ['device_name']]]);
    }

    public function test_login_rate_limit_returns_json_429(): void
    {
        $payload = [
            'email' => 'limited@example.test',
            'password' => 'wrong',
            'device_name' => 'Owl iPhone',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', $payload)->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', $payload)
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'too_many_requests');
    }

    public function test_plaintext_token_is_not_stored_and_bearer_authenticates(): void
    {
        $user = $this->makeLinkedActor('student', 'token@example.test');
        $plain = $this->loginToken($user, 'Phone A');

        $this->assertDatabaseMissing('personal_access_tokens', ['token' => $plain]);
        $this->assertFalse(
            DB::table('personal_access_tokens')->where('token', $plain)->exists()
        );

        $stored = PersonalAccessToken::query()->first();
        $this->assertNotNull($stored);
        $this->assertNotSame($plain, $stored->token);
        $this->assertSame(64, strlen((string) $stored->token));

        $this->withToken($plain)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $user->email);
    }

    public function test_multiple_device_tokens_are_allowed(): void
    {
        $user = $this->makeLinkedActor('teacher', 'devices@example.test');
        $first = $this->loginToken($user, 'Phone A');
        $second = $this->loginToken($user, 'Phone B');

        $this->assertNotSame($first, $second);
        $this->assertSame(2, $user->tokens()->count());

        $this->withToken($first)->getJson('/api/v1/me')->assertOk();
        $this->withToken($second)->getJson('/api/v1/me')->assertOk();
    }

    public function test_logout_revokes_only_current_token(): void
    {
        $user = $this->makeLinkedActor('parent', 'logout@example.test');
        $current = $this->loginToken($user, 'Phone A');
        $other = $this->loginToken($user, 'Phone B');

        $this->withToken($current)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->forgetAuthGuards();

        $this->withToken($current)->getJson('/api/v1/me')->assertUnauthorized();
        $this->withToken($other)->getJson('/api/v1/me')->assertOk();
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_logout_all_revokes_all_user_tokens(): void
    {
        $user = $this->makeLinkedActor('student', 'logout-all@example.test');
        $first = $this->loginToken($user, 'Phone A');
        $second = $this->loginToken($user, 'Phone B');

        $this->withToken($first)
            ->postJson('/api/v1/auth/logout-all')
            ->assertOk();

        $this->forgetAuthGuards();

        $this->withToken($first)->getJson('/api/v1/me')->assertUnauthorized();
        $this->withToken($second)->getJson('/api/v1/me')->assertUnauthorized();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_student_me_is_whitelisted(): void
    {
        AcademicYear::query()->update(['is_active' => false]);
        $year = AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-06-30',
            'is_active' => true,
        ]);
        $class = $this->makeClass('Prima A');
        $student = $this->makeStudent('Mario', 'Rossi', [
            'tax_code' => 'RSSMRA10A01H501X',
            'notes' => 'internal note',
            'medical_certificate_expiry' => '2027-01-01',
            'parent_id_document_path' => 'students/1/id.pdf',
        ]);
        $class->students()->attach($student->id);
        $user = $this->identity->createAndLink($student, [
            'name' => 'Mario Rossi',
            'email' => 'mario@example.test',
            'password' => 'password',
        ]);

        $profile = $this->withToken($this->loginToken($user, 'Owl iPhone'))
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.account_type', 'student')
            ->assertJsonPath('data.profile.id', $student->id)
            ->assertJsonPath('data.profile.first_name', 'Mario')
            ->assertJsonPath('data.profile.last_name', 'Rossi')
            ->assertJsonPath('data.profile.academy_class.id', $class->id)
            ->assertJsonPath('data.profile.academy_class.name', 'Prima A')
            ->assertJsonPath('data.profile.academic_year.id', $year->id)
            ->json('data');

        $this->assertSame(['id', 'name', 'email', 'account_type'], array_keys($profile['user']));
        $this->assertSame(
            [
                'id',
                'first_name',
                'last_name',
                'display_name',
                'photo_url',
                'photo',
                'phone',
                'birth_date',
                'residence_address',
                'residence_city_province',
                'residence_postal_code',
                'academy_class',
                'academic_year',
            ],
            array_keys($profile['profile']),
        );
        $this->assertSensitiveKeysAbsent(json_encode($profile, JSON_THROW_ON_ERROR));
    }

    public function test_parent_me_shows_only_linked_children(): void
    {
        $ownClass = $this->makeClass('Own Class');
        $otherClass = $this->makeClass('Other Class');
        $ownChild = $this->makeStudent('Giulia', 'Verdi');
        $otherChild = $this->makeStudent('Marco', 'Neri', ['tax_code' => 'NREMRC10A01H501X']);
        $ownClass->students()->attach($ownChild->id);
        $otherClass->students()->attach($otherChild->id);

        $parent = $this->makeParent('Maria', 'Verdi');
        $stranger = $this->makeParent('Luca', 'Neri');
        $parent->students()->attach($ownChild->id, ['relation_type' => 'mother']);
        $stranger->students()->attach($otherChild->id, ['relation_type' => 'father']);

        $user = $this->identity->createAndLink($parent, [
            'name' => 'Maria Verdi',
            'email' => 'parent@example.test',
            'password' => 'password',
        ]);

        $children = $this->withToken($this->loginToken($user, 'Owl iPhone'))
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.account_type', 'parent')
            ->assertJsonPath('data.profile.children.0.id', $ownChild->id)
            ->assertJsonPath('data.profile.children.0.academy_class.id', $ownClass->id)
            ->json('data.profile.children');

        $this->assertCount(1, $children);
        $this->assertSame($ownChild->id, $children[0]['id']);
        $this->assertSame(
            ['id', 'first_name', 'last_name', 'display_name', 'photo_url', 'academy_class'],
            array_keys($children[0]),
        );
        $this->assertSensitiveKeysAbsent(json_encode($children, JSON_THROW_ON_ERROR));
    }

    public function test_teacher_me_is_compact(): void
    {
        $teacher = $this->makeTeacher('Elena', 'Bianchi');
        $user = $this->identity->createAndLink($teacher, [
            'name' => 'Elena Bianchi',
            'email' => 'teacher@example.test',
            'password' => 'password',
        ]);

        $profile = $this->withToken($this->loginToken($user, 'Owl iPhone'))
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.account_type', 'teacher')
            ->json('data.profile');

        $this->assertSame(
            ['id', 'first_name', 'last_name', 'display_name', 'photo_url', 'photo'],
            array_keys($profile),
        );
        $this->assertSame('Elena Bianchi', $profile['display_name']);
        $this->assertSensitiveKeysAbsent(json_encode($profile, JSON_THROW_ON_ERROR));
    }

    public function test_disabled_user_loses_api_access(): void
    {
        $user = $this->makeLinkedActor('student', 'disabled-later@example.test');
        $token = $this->loginToken($user, 'Owl iPhone');

        $this->withToken($token)->getJson('/api/v1/me')->assertOk();

        $this->identity->setActive($user->fresh(), false);

        $this->forgetAuthGuards();

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');

        $this->assertSame(0, $user->fresh()->tokens()->count());
    }

    public function test_unlinked_profile_loses_api_access(): void
    {
        $student = $this->makeStudent('Nora', 'Conti');
        $user = $this->identity->createAndLink($student, [
            'name' => 'Nora Conti',
            'email' => 'unlinked@example.test',
            'password' => 'password',
        ]);
        $token = $this->loginToken($user, 'Owl iPhone');

        $this->identity->unlink($student->fresh());

        $this->forgetAuthGuards();

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_unauthenticated_and_missing_api_routes_use_json_envelope(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertExactJson([
                'success' => false,
                'error' => [
                    'code' => 'unauthenticated',
                    'message' => 'Unauthenticated.',
                ],
            ]);

        $this->getJson('/api/v1/does-not-exist')
            ->assertNotFound()
            ->assertExactJson([
                'success' => false,
                'error' => [
                    'code' => 'not_found',
                    'message' => 'Not found.',
                ],
            ]);
    }

    private function forgetAuthGuards(): void
    {
        $this->app['auth']->forgetGuards();
    }

    private function loginToken(User $user, string $deviceName): string
    {
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => $deviceName,
        ])->assertOk()->json('data.token');

        $this->assertIsString($token);

        return $token;
    }

    private function makeLinkedActor(string $type, string $email, bool $active = true): User
    {
        $profile = match ($type) {
            'student' => $this->makeStudent('Anna', 'Rossi'),
            'parent' => $this->makeParent('Maria', 'Verdi'),
            'teacher' => $this->makeTeacher('Mario', 'Rossi'),
            default => throw new \InvalidArgumentException($type),
        };

        return $this->identity->createAndLink($profile, [
            'name' => $profile instanceof Teacher ? $profile->displayName() : $profile->displayName(),
            'email' => $email,
            'password' => 'password',
            'is_active' => $active,
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

    private function makeParent(string $firstName, string $lastName): AcademyParent
    {
        return AcademyParent::query()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower($firstName.'.'.$lastName.'.'.uniqid('', true)).'@example.test',
        ]);
    }

    private function makeTeacher(string $firstName, string $lastName): Teacher
    {
        return Teacher::query()->create([
            'type' => 'permanent',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => trim($firstName.' '.$lastName),
        ]);
    }

    private function makeClass(string $name): AcademyClass
    {
        $course = Course::query()->first() ?? Course::query()->create([
            'discipline' => 'academy',
            'name' => 'Classical Academy',
            'sort_order' => 1,
        ]);

        return AcademyClass::query()->create([
            'course_id' => $course->id,
            'name' => $name,
            'color' => '#1A2B44',
            'sort_order' => AcademyClass::query()->count() + 1,
        ]);
    }

    private function makeStaffAdmin(): User
    {
        $role = Role::query()->where('slug', 'administrator')->firstOrFail();

        return User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin-api@example.test',
            'role_id' => $role->id,
            'account_type' => User::TYPE_STAFF,
            'is_active' => true,
            'can_write' => true,
            'can_delete' => true,
            'password' => 'password',
        ]);
    }

    private function assertSensitiveKeysAbsent(string $json): void
    {
        foreach ([
            'tax_code',
            'medical_certificate',
            'notes',
            'password',
            'remember_token',
            'can_write',
            'can_delete',
            'role_id',
            'parent_id_document',
            'general_regulation',
            'api_key',
        ] as $fragment) {
            $this->assertStringNotContainsString('"'.$fragment, $json);
        }
    }
}
