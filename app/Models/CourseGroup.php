<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CourseGroup extends Model
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

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'course_group_customer')
            ->withPivot('discipline')
            ->withTimestamps()
            ->orderBy('customers.name');
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'course_group_lesson')
            ->withPivot('teacher_id', 'hours')
            ->withTimestamps()
            ->orderBy('lessons.name');
    }
}
