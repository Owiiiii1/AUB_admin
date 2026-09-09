<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    public const STATUS_PRESENT = 'present';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_EXCUSED = 'excused';

    /**
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_PRESENT,
        self::STATUS_ABSENT,
        self::STATUS_EXCUSED,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'scheduled_lesson_id',
        'student_id',
        'status',
        'marked_by',
        'marked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'marked_at' => 'datetime',
        ];
    }

    public function scheduledLesson(): BelongsTo
    {
        return $this->belongsTo(ScheduledLesson::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::STATUSES, true);
    }
}
