<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Lesson extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'discipline',
        'name',
        'description',
        'duration_minutes',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'duration_minutes' => 'integer',
        ];
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'lesson_teacher')
            ->withTimestamps()
            ->orderBy('teachers.name');
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'lesson_course')
            ->withTimestamps()
            ->orderBy('courses.sort_order')
            ->orderBy('courses.name');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(CourseGroup::class, 'course_group_lesson')
            ->withPivot('teacher_id', 'hours')
            ->withTimestamps();
    }
}
