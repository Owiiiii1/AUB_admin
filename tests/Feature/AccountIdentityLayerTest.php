<?php

namespace Tests\Feature;

use App\Exceptions\AccountIdentityException;
use App\Models\AcademyParent;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AccountIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AccountIdentityLayerTest extends TestCase
{
    use RefreshDatabase;

    private AccountIdentityService $identity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->identity = $this->app->make(AccountIdentityService::class);
    }

    public function test_existing_users_are_staff_after_migration(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'account_type'));
        $this->assertTrue(Schema::hasColumn('users', 'is_active'));
        $this->assertTrue(Schema::hasColumn('students', 'user_id'));
        $this->assertTrue(Schema::hasColumn('parents', 'user_id'));
        $this->assertTrue(Schema::hasColumn('teachers', 'user_id'));

        $admin = $this->makeStaffAdmin();

        $this->assertSame(User::TYPE_STAFF, $admin->account_type);
        $this->assertTrue($admin->is_active);
        $this->assertNotNull($admin->role_id);
        $this->assertTrue($admin->role?->is_admin);
    }

    public function test_allowed_account_types_are_accepted(): void
    {
        foreach (User::ACCOUNT_TYPES as $type) {
            $user = User::factory()->create([
                'account_type' => $type,
                'role_id' => $type === User::TYPE_STAFF ? $this->adminRole()->id : null,
            ]);

            $this->assertSame($type, $user->fresh()->account_type);
        }
    }

    public function test_invalid_account_type_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        User::factory()->create([
            'account_type' => 'alien',
        ]);
    }

    public function test_student_user_can_be_linked_to_one_student_only(): void
    {
        $user = User::factory()->student()->create();
        $student = $this->makeStudent('Anna', 'Rossi');
        $other = $this->makeStudent('Luca', 'Bianchi');

        $this->identity->link($user, $student);

        $this->assertSame($user->id, $student->fresh()->user_id);
        $this->assertTrue($user->fresh()->studentProfile->is($student));

        $this->expectException(AccountIdentityException::class);
        $this->identity->link($user, $other);
    }

    public function test_student_user_cannot_be_linked_to_parent_or_teacher(): void
    {
        $user = User::factory()->student()->create();
        $parent = $this->makeParent();
        $teacher = $this->makeTeacher('Mario', 'Rossi');

        try {
            $this->identity->link($user, $parent);
            $this->fail('Student user was linked to a parent.');
        } catch (AccountIdentityException) {
            $this->assertTrue(true);
        }

        $this->expectException(AccountIdentityException::class);
        $this->identity->link($user, $teacher);
    }

    public function test_parent_user_can_be_linked_to_parent_but_not_student_or_teacher(): void
    {
        $user = User::factory()->parentAccount()->create();
        $parent = $this->makeParent();
        $student = $this->makeStudent('Giulia', 'Verdi');
        $teacher = $this->makeTeacher('Elena', 'Neri');

        $this->identity->link($user, $parent);
        $this->assertSame($user->id, $parent->fresh()->user_id);

        try {
            $this->identity->link($user, $student);
            $this->fail('Parent user was linked to a student.');
        } catch (AccountIdentityException) {
            $this->assertTrue(true);
        }

        $this->expectException(AccountIdentityException::class);
        $this->identity->link($user, $teacher);
    }

    public function test_teacher_user_can_be_linked_to_teacher_but_not_student_or_parent(): void
    {
        $user = User::factory()->teacherAccount()->create();
        $teacher = $this->makeTeacher('Paolo', 'Blu');
        $student = $this->makeStudent('Sara', 'Blu');
        $parent = $this->makeParent();

        $this->identity->link($user, $teacher);
        $this->assertSame($user->id, $teacher->fresh()->user_id);
        $this->assertTrue($user->fresh()->teacherProfile->is($teacher));

        try {
            $this->identity->link($user, $student);
            $this->fail('Teacher user was linked to a student.');
        } catch (AccountIdentityException) {
            $this->assertTrue(true);
        }

        $this->expectException(AccountIdentityException::class);
        $this->identity->link($user, $parent);
    }

    public function test_staff_user_cannot_have_actor_profile_and_keeps_rbac(): void
    {
        $admin = $this->makeStaffAdmin();
        $student = $this->makeStudent('Staff', 'Link');

        $this->assertNull($admin->studentProfile);
        $this->assertNull($admin->parentProfile);
        $this->assertNull($admin->teacherProfile);
        $this->assertTrue($admin->canAccessWebAdmin());
        $this->assertTrue($admin->isAdministrator());

        $this->expectException(AccountIdentityException::class);
        $this->identity->link($admin, $student);
    }

    public function test_parent_and_student_without_role_cannot_access_admin(): void
    {
        $parentUser = User::factory()->parentAccount()->create();
        $studentUser = User::factory()->student()->create();

        $this->assertFalse($parentUser->canAccessWebAdmin());
        $this->assertFalse($studentUser->canAccessWebAdmin());
        $this->assertNull($parentUser->role_id);
        $this->assertNull($studentUser->role_id);

        $this->actingAs($parentUser)->get('/customers')->assertForbidden();
        $this->actingAs($studentUser)->get('/workplace')->assertForbidden();
        $this->actingAs($studentUser)->get('/dashboard')->assertForbidden();
    }

    public function test_account_type_does_not_grant_web_permissions(): void
    {
        $teacher = User::factory()->teacherAccount()->create();
        $staffWithoutRole = User::factory()->create([
            'account_type' => User::TYPE_STAFF,
            'role_id' => null,
        ]);

        $this->assertFalse($teacher->canAccessWebAdmin());
        $this->assertFalse($staffWithoutRole->canAccessWebAdmin());
        $this->actingAs($teacher)->get('/customers')->assertForbidden();
        $this->actingAs($staffWithoutRole)->get('/settings')->assertForbidden();
    }

    public function test_inactive_user_cannot_web_login(): void
    {
        $admin = $this->makeStaffAdmin([
            'email' => 'inactive-admin@example.test',
            'is_active' => false,
        ]);

        $this->from('/')->post('/', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_active_staff_admin_can_web_login(): void
    {
        $admin = $this->makeStaffAdmin([
            'email' => 'active-admin@example.test',
        ]);

        $this->from('/')->post('/', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_parent_web_login_is_rejected(): void
    {
        $parentUser = User::factory()->parentAccount()->create([
            'email' => 'parent@example.test',
            'password' => 'password',
        ]);

        $this->from('/')->post('/', [
            'email' => $parentUser->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_create_and_link_student_account(): void
    {
        $admin = $this->makeStaffAdmin();
        $student = $this->makeStudent('Nora', 'Conti');

        $this->actingAs($admin)
            ->from(route('customers.show', $student))
            ->post(route('customers.account.store', $student), [
                'name' => 'Nora Conti',
                'email' => 'nora.student@example.test',
                'password' => 'temporary-pass',
                'is_active' => true,
            ])
            ->assertRedirect();

        $student->refresh();
        $this->assertNotNull($student->user_id);
        $user = $student->user;
        $this->assertSame(User::TYPE_STUDENT, $user->account_type);
        $this->assertNull($user->role_id);
        $this->assertTrue(Hash::check('temporary-pass', $user->password));
        $this->assertFalse($user->canAccessWebAdmin());
    }

    public function test_student_and_parent_cannot_receive_web_role(): void
    {
        $this->expectException(ValidationException::class);

        User::factory()->student()->create([
            'role_id' => $this->adminRole()->id,
        ]);
    }

    public function test_create_and_link_does_not_store_plaintext_password(): void
    {
        $student = $this->makeStudent('Plain', 'Text');
        $user = $this->identity->createAndLink($student, [
            'name' => 'Plain Text',
            'email' => 'plain@example.test',
            'password' => 'secret-pass',
        ]);

        $this->assertNotSame('secret-pass', $user->getAttributes()['password']);
        $this->assertTrue(Hash::check('secret-pass', $user->password));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeStaffAdmin(array $overrides = []): User
    {
        return User::factory()->create([
            'name' => 'Administrator',
            'role_id' => $this->adminRole()->id,
            'account_type' => User::TYPE_STAFF,
            'is_active' => true,
            'can_write' => true,
            'can_delete' => true,
            'password' => 'password',
            ...$overrides,
        ]);
    }

    private function adminRole(): Role
    {
        return Role::query()->where('slug', 'administrator')->firstOrFail();
    }

    private function makeStudent(string $firstName, string $lastName): Student
    {
        return Student::query()->create([
            'name' => trim($firstName.' '.$lastName),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'status' => 'active',
        ]);
    }

    private function makeParent(): AcademyParent
    {
        return AcademyParent::query()->create([
            'first_name' => 'Maria',
            'last_name' => 'Verdi',
            'email' => 'maria.'.uniqid('', true).'@example.test',
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
}
