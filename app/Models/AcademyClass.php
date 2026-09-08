<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademyClass extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'course_id',
        'name',
        'color',
        'sort_order',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'academy_class_student')
            ->withTimestamps()
            ->orderBy('students.name');
    }

    public function classLessons(): HasMany
    {
        return $this->hasMany(ClassLesson::class);
    }

    public function scheduledLessons(): HasMany
    {
        return $this->hasMany(ScheduledLesson::class);
    }
}
