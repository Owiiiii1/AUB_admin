<?php

namespace App\Services;

use App\Exceptions\AccountIdentityException;
use App\Models\AcademyParent;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountIdentityService
{
    public function createStaffUser(array $data): User
    {
        $this->assertValidType(User::TYPE_STAFF);

        return User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'account_type' => User::TYPE_STAFF,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'role_id' => $data['role_id'],
            'can_write' => (bool) ($data['can_write'] ?? false),
            'can_delete' => (bool) ($data['can_delete'] ?? false),
        ]);
    }

    /**
     * @param  array{name: string, email: string, password: string, is_active?: bool, role_id?: int|null}  $credentials
     */
    public function createAndLink(Student|AcademyParent|Teacher $profile, array $credentials): User
    {
        $type = $this->typeForProfile($profile);

        return DB::transaction(function () use ($profile, $credentials, $type): User {
            $roleId = $type === User::TYPE_TEACHER
                ? ($credentials['role_id'] ?? null)
                : null;

            $user = User::query()->create([
                'name' => $credentials['name'],
                'email' => $credentials['email'],
                'password' => $credentials['password'],
                'account_type' => $type,
                'is_active' => (bool) ($credentials['is_active'] ?? true),
                'role_id' => $roleId,
                'can_write' => false,
                'can_delete' => false,
            ]);

            $this->link($user, $profile);

            return $user;
        });
    }

    public function link(User $user, Student|AcademyParent|Teacher $profile): void
    {
        $this->assertCanLink($user, $profile);

        $profile->forceFill(['user_id' => $user->id])->save();
    }

    public function unlink(Student|AcademyParent|Teacher $profile): void
    {
        $profile->forceFill(['user_id' => null])->save();
    }

    public function setActive(User $user, bool $active): void
    {
        $user->is_active = $active;
        $user->save();
    }

    /**
     * @return list<array{id: int, name: string, email: string}>
     */
    public function linkableUsers(string $accountType): array
    {
        $this->assertValidType($accountType);

        if ($accountType === User::TYPE_STAFF) {
            return [];
        }

        return User::query()
            ->where('account_type', $accountType)
            ->whereDoesntHave('studentProfile')
            ->whereDoesntHave('parentProfile')
            ->whereDoesntHave('teacherProfile')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(static fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->all();
    }

    public function assertCanLink(User $user, Student|AcademyParent|Teacher $profile): void
    {
        $expectedType = $this->typeForProfile($profile);

        if ($user->account_type === User::TYPE_STAFF) {
            throw $this->fail('A staff account cannot be linked to a student, parent, or teacher profile.');
        }

        if ($user->account_type !== $expectedType) {
            throw $this->fail('This account type cannot be linked to this profile.');
        }

        if ($profile->user_id !== null && (int) $profile->user_id !== (int) $user->id) {
            throw $this->fail('This profile is already linked to another account.');
        }

        $linkedProfile = $this->linkedProfile($user);
        if ($linkedProfile !== null && ! $this->sameProfile($linkedProfile, $profile)) {
            throw $this->fail('This account is already linked to another actor profile.');
        }

        $this->assertNoCrossLinks($user, $profile);
    }

    public function typeForProfile(Student|AcademyParent|Teacher $profile): string
    {
        return match (true) {
            $profile instanceof Student => User::TYPE_STUDENT,
            $profile instanceof AcademyParent => User::TYPE_PARENT,
            $profile instanceof Teacher => User::TYPE_TEACHER,
        };
    }

    public function assertValidType(string $type): void
    {
        if (! in_array($type, User::ACCOUNT_TYPES, true)) {
            throw $this->fail('Invalid account type.');
        }
    }

    public function toValidationException(AccountIdentityException $exception, string $key = 'account'): ValidationException
    {
        return ValidationException::withMessages([
            $key => $exception->getMessage(),
        ]);
    }

    private function assertNoCrossLinks(User $user, Student|AcademyParent|Teacher $profile): void
    {
        $studentId = Student::query()->where('user_id', $user->id)->value('id');
        $parentId = AcademyParent::query()->where('user_id', $user->id)->value('id');
        $teacherId = Teacher::query()->where('user_id', $user->id)->value('id');

        if ($profile instanceof Student && ($parentId !== null || $teacherId !== null || ($studentId !== null && (int) $studentId !== (int) $profile->id))) {
            throw $this->fail('This account cannot be linked to a student because it already has another actor profile.');
        }

        if ($profile instanceof AcademyParent && ($studentId !== null || $teacherId !== null || ($parentId !== null && (int) $parentId !== (int) $profile->id))) {
            throw $this->fail('This account cannot be linked to a parent because it already has another actor profile.');
        }

        if ($profile instanceof Teacher && ($studentId !== null || $parentId !== null || ($teacherId !== null && (int) $teacherId !== (int) $profile->id))) {
            throw $this->fail('This account cannot be linked to a teacher because it already has another actor profile.');
        }
    }

    private function linkedProfile(User $user): Student|AcademyParent|Teacher|null
    {
        return Student::query()->where('user_id', $user->id)->first()
            ?? AcademyParent::query()->where('user_id', $user->id)->first()
            ?? Teacher::query()->where('user_id', $user->id)->first();
    }

    private function sameProfile(Model $left, Model $right): bool
    {
        return $left::class === $right::class && (int) $left->getKey() === (int) $right->getKey();
    }

    private function fail(string $message): AccountIdentityException
    {
        return new AccountIdentityException($message);
    }
}
