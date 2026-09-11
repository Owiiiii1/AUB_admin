<?php

namespace Tests\Feature;

use App\Models\AcademyParent;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AccountIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    private AccountIdentityService $identity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->identity = $this->app->make(AccountIdentityService::class);
    }

    public function test_student_me_includes_contact_fields_without_sensitive_data(): void
    {
        $student = Student::query()->create([
            'name' => 'Mario Rossi',
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'status' => 'active',
            'phone' => '+39 333 120 8801',
            'birth_date' => '2009-04-18',
            'residence_address' => 'Via Padova 128',
            'residence_city_province' => 'Milano (MI)',
            'residence_postal_code' => '20127',
            'tax_code' => 'RSSMRA10A01H501X',
            'notes' => 'internal note',
        ]);
        $user = $this->identity->createAndLink($student, [
            'name' => 'Mario Rossi',
            'email' => 'mario-contact@example.test',
            'password' => 'password',
        ]);

        $profile = $this->withToken($this->loginToken($user, 'Owl iPhone'))
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.profile.phone', '+39 333 120 8801')
            ->assertJsonPath('data.profile.birth_date', '2009-04-18')
            ->assertJsonPath('data.profile.residence_address', 'Via Padova 128')
            ->assertJsonPath('data.profile.residence_city_province', 'Milano (MI)')
            ->assertJsonPath('data.profile.residence_postal_code', '20127')
            ->json('data.profile');

        $this->assertArrayNotHasKey('tax_code', $profile);
        $this->assertArrayNotHasKey('notes', $profile);
        $encoded = json_encode($profile, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('"tax_code"', $encoded);
        $this->assertStringNotContainsString('"notes"', $encoded);
    }

    public function test_password_change_keeps_current_device_and_revokes_others(): void
    {
        $user = $this->makeLinkedStudent('pwd@example.test');
        $first = $this->loginToken($user, 'Phone A');
        $this->forgetAuthGuards();
        $second = $this->loginToken($user, 'Phone B');

        $this->withToken($second)
            ->putJson('/api/v1/me/password', [
                'current_password' => 'password',
                'password' => 'password1',
                'password_confirmation' => 'password1',
            ])
            ->assertOk()
            ->assertJsonPath('data.updated', true);

        $this->assertSame(1, $user->fresh()->tokens()->count());
        $this->assertTrue(
            PersonalAccessToken::findToken($second) !== null
        );
        $this->assertNull(PersonalAccessToken::findToken($first));

        $this->forgetAuthGuards();
        $this->withToken($second)
            ->getJson('/api/v1/me')
            ->assertOk();

        $this->forgetAuthGuards();
        $this->postJson('/api/v1/auth/login', [
            'email' => 'pwd@example.test',
            'password' => 'password1',
            'device_name' => 'Phone C',
        ])->assertOk();
    }

    public function test_wrong_current_password_is_validation_error(): void
    {
        $user = $this->makeLinkedStudent('pwd-wrong@example.test');

        $this->withToken($this->loginToken($user, 'Owl iPhone'))
            ->putJson('/api/v1/me/password', [
                'current_password' => 'nope-nope',
                'password' => 'password1',
                'password_confirmation' => 'password1',
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error')
            ->assertJsonStructure(['error' => ['fields' => ['current_password']]]);
    }

    public function test_devices_list_marks_current_and_can_revoke_other(): void
    {
        $user = $this->makeLinkedStudent('devices@example.test');
        $first = $this->loginToken($user, 'Phone A');
        $this->forgetAuthGuards();
        $second = $this->loginToken($user, 'Phone B');

        $devices = $this->withToken($second)
            ->getJson('/api/v1/me/devices')
            ->assertOk()
            ->json('data.devices');

        $this->assertCount(2, $devices);
        $names = array_column($devices, 'name');
        $this->assertContains('Phone A', $names);
        $this->assertContains('Phone B', $names);
        $this->assertContainsOnly('int', array_column($devices, 'id'));

        $current = collect($devices)->firstWhere('current', true);
        $other = collect($devices)->firstWhere('current', false);
        $this->assertNotNull($current);
        $this->assertNotNull($other);
        $this->assertSame('Phone B', $current['name']);
        $this->assertArrayNotHasKey('token', $current);
        $this->assertArrayNotHasKey('tokenable_id', $current);

        $this->withToken($second)
            ->deleteJson('/api/v1/me/devices/'.$other['id'])
            ->assertOk()
            ->assertJsonPath('data.id', $other['id']);

        $this->assertSame(1, $user->fresh()->tokens()->count());
        $this->assertNull(PersonalAccessToken::findToken($first));

        $this->withToken($second)
            ->deleteJson('/api/v1/me/devices/'.$current['id'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');

        $this->withToken($second)
            ->deleteJson('/api/v1/me/devices/999999')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    public function test_cannot_revoke_another_users_device(): void
    {
        $owner = $this->makeLinkedStudent('owner-device@example.test');
        $stranger = $this->makeLinkedStudent('stranger-device@example.test');
        $ownerToken = $this->loginToken($owner, 'Owner Phone');
        $this->forgetAuthGuards();
        $strangerToken = $this->loginToken($stranger, 'Stranger Phone');

        $ownerDeviceId = $owner->fresh()->tokens()->first()?->id;
        $this->assertNotNull($ownerDeviceId);

        $this->withToken($strangerToken)
            ->deleteJson('/api/v1/me/devices/'.$ownerDeviceId)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');

        $this->assertNotNull(PersonalAccessToken::findToken($ownerToken));
    }

    public function test_parent_and_teacher_can_list_devices(): void
    {
        $parent = AcademyParent::query()->create([
            'first_name' => 'Maria',
            'last_name' => 'Verdi',
            'email' => 'maria.devices@example.test',
        ]);
        $parentUser = $this->identity->createAndLink($parent, [
            'name' => 'Maria Verdi',
            'email' => 'parent-devices@example.test',
            'password' => 'password',
        ]);
        $this->withToken($this->loginToken($parentUser, 'Parent Phone'))
            ->getJson('/api/v1/me/devices')
            ->assertOk()
            ->assertJsonPath('data.devices.0.name', 'Parent Phone')
            ->assertJsonPath('data.devices.0.current', true);

        $teacher = Teacher::query()->create([
            'type' => 'permanent',
            'first_name' => 'Elena',
            'last_name' => 'Bianchi',
            'name' => 'Elena Bianchi',
        ]);
        $teacherUser = $this->identity->createAndLink($teacher, [
            'name' => 'Elena Bianchi',
            'email' => 'teacher-devices@example.test',
            'password' => 'password',
        ]);
        $this->forgetAuthGuards();
        $this->withToken($this->loginToken($teacherUser, 'Teacher Phone'))
            ->getJson('/api/v1/me/devices')
            ->assertOk()
            ->assertJsonPath('data.devices.0.name', 'Teacher Phone');
    }

    private function makeLinkedStudent(string $email): User
    {
        $student = Student::query()->create([
            'name' => 'Anna Rossi',
            'first_name' => 'Anna',
            'last_name' => 'Rossi',
            'status' => 'active',
            'email' => strtolower($email.'.student@example.test'),
        ]);

        return $this->identity->createAndLink($student, [
            'name' => 'Anna Rossi',
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function loginToken(User $user, string $deviceName): string
    {
        $this->flushHeaders();
        $this->forgetAuthGuards();

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => $deviceName,
        ])->assertOk()->json('data.token');

        $this->assertIsString($token);

        return $token;
    }

    private function forgetAuthGuards(): void
    {
        $this->app['auth']->forgetGuards();
    }
}
