<?php

namespace App\Models;

use App\Services\WeeklySchedule\ScheduleConflictService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleWeek extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_LOCKED = 'locked';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'week_start_date',
        'week_end_date',
        'title',
        'status',
        'work_starts_at',
        'work_ends_at',
        'published_at',
        'published_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'week_start_date' => 'date',
            'week_end_date' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public function workStartsAt(): string
    {
        return $this->formatWorkTime($this->work_starts_at) ?? ScheduleConflictService::GRID_START;
    }

    public function workEndsAt(): string
    {
        return $this->formatWorkTime($this->work_ends_at) ?? ScheduleConflictService::GRID_END;
    }

    private function formatWorkTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = is_string($value) ? $value : (string) $value;
        $candidate = substr($raw, 0, 5);

        if (! preg_match('/^\d{2}:\d{2}$/', $candidate)) {
            return null;
        }

        return $candidate;
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function scheduledLessons(): HasMany
    {
        return $this->hasMany(ScheduledLesson::class);
    }

    public function aiRuns(): HasMany
    {
        return $this->hasMany(ScheduleAiRun::class);
    }

    public function isEditable(): bool
    {
        return $this->status !== self::STATUS_LOCKED;
    }
}
