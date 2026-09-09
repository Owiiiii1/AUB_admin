<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduledLesson extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_MOVED = 'moved';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'schedule_week_id',
        'academy_building_id',
        'academy_room_id',
        'academy_class_id',
        'teacher_id',
        'lesson_id',
        'lesson_date',
        'starts_at',
        'ends_at',
        'title',
        'notes',
        'color',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lesson_date' => 'date',
        ];
    }

    public function scheduleWeek(): BelongsTo
    {
        return $this->belongsTo(ScheduleWeek::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(AcademyBuilding::class, 'academy_building_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(AcademyRoom::class, 'academy_room_id');
    }

    public function academyClass(): BelongsTo
    {
        return $this->belongsTo(AcademyClass::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
