<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role_id', 'can_delete', 'can_write', 'account_type', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const TYPE_STAFF = 'staff';

    public const TYPE_STUDENT = 'student';

    public const TYPE_PARENT = 'parent';

    public const TYPE_TEACHER = 'teacher';

    /**
     * @var list<string>
     */
    public const ACCOUNT_TYPES = [
        self::TYPE_STAFF,
        self::TYPE_STUDENT,
        self::TYPE_PARENT,
        self::TYPE_TEACHER,
    ];

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'account_type' => self::TYPE_STAFF,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if (! in_array($user->account_type, self::ACCOUNT_TYPES, true)) {
                throw ValidationException::withMessages([
                    'account_type' => 'Invalid account type.',
                ]);
            }

            if (in_array($user->account_type, [self::TYPE_STUDENT, self::TYPE_PARENT], true) && $user->role_id !== null) {
                throw ValidationException::withMessages([
                    'role_id' => 'Student and parent accounts cannot have a web role.',
                ]);
            }
        });

        static::updated(function (User $user): void {
            if ($user->wasChanged('is_active') && ! $user->is_active) {
                $user->tokens()->delete();
            }
        });
    }

    /**
     * @return list<string>
     */
    public static function mobileAccountTypes(): array
    {
        return [
            self::TYPE_STUDENT,
            self::TYPE_PARENT,
            self::TYPE_TEACHER,
        ];
    }

    public function isMobileActor(): bool
    {
        return in_array($this->account_type, self::mobileAccountTypes(), true);
    }

    public function matchingActorProfile(): Student|AcademyParent|Teacher|null
    {
        return match ($this->account_type) {
            self::TYPE_STUDENT => Student::query()->where('user_id', $this->id)->first(),
            self::TYPE_PARENT => AcademyParent::query()->where('user_id', $this->id)->first(),
            self::TYPE_TEACHER => Teacher::query()->where('user_id', $this->id)->first(),
            default => null,
        };
    }

    public function canAccessMobileApi(): bool
    {
        return $this->is_active && $this->isMobileActor() && $this->matchingActorProfile() !== null;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function parentProfile(): HasOne
    {
        return $this->hasOne(AcademyParent::class);
    }

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function isAdministrator(): bool
    {
        return (bool) $this->role?->is_admin;
    }

    public function hasAssignedRole(): bool
    {
        return $this->role_id !== null && $this->role !== null && $this->role->is_active;
    }

    public function canAccessWebAdmin(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->account_type === self::TYPE_STUDENT || $this->account_type === self::TYPE_PARENT) {
            return false;
        }

        return $this->hasAssignedRole();
    }

    public function isStaff(): bool
    {
        return $this->account_type === self::TYPE_STAFF;
    }

    /**
     * @return array<string, mixed>
     */
    public function accountPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'account_type' => $this->account_type,
            'is_active' => (bool) $this->is_active,
            'role_id' => $this->role_id,
            'role_name' => $this->role?->name,
            'linked_profile' => $this->linkedProfileSummary(),
        ];
    }

    /**
     * @return array{type: string, id: int, label: string}|null
     */
    public function linkedProfileSummary(): ?array
    {
        if ($this->studentProfile !== null) {
            return [
                'type' => self::TYPE_STUDENT,
                'id' => $this->studentProfile->id,
                'label' => $this->studentProfile->displayName(),
            ];
        }

        if ($this->parentProfile !== null) {
            return [
                'type' => self::TYPE_PARENT,
                'id' => $this->parentProfile->id,
                'label' => $this->parentProfile->displayName(),
            ];
        }

        if ($this->teacherProfile !== null) {
            return [
                'type' => self::TYPE_TEACHER,
                'id' => $this->teacherProfile->id,
                'label' => $this->teacherProfile->name ?: trim($this->teacherProfile->first_name.' '.$this->teacherProfile->last_name),
            ];
        }

        return null;
    }

    public function canDelete(): bool
    {
        return (bool) $this->can_delete;
    }

    public function canWrite(): bool
    {
        return (bool) $this->can_write;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'can_delete' => 'boolean',
            'can_write' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
