<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountDeletionRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    public const SOURCE_WEB = 'web';

    public const SOURCE_APP = 'app';

    public const ROLE_STUDENT = 'student';

    public const ROLE_PARENT = 'parent';

    public const ROLE_TEACHER = 'teacher';

    public const ROLE_OTHER = 'other';

    /**
     * @var list<string>
     */
    public const ROLES = [
        self::ROLE_STUDENT,
        self::ROLE_PARENT,
        self::ROLE_TEACHER,
        self::ROLE_OTHER,
    ];

    /**
     * @var list<string>
     */
    public const OPEN_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_VERIFIED,
        self::STATUS_PROCESSING,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'email',
        'account_type',
        'source',
        'status',
        'message',
        'resolution_note',
        'requested_at',
        'verified_at',
        'processed_at',
        'processed_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'verified_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }
}
