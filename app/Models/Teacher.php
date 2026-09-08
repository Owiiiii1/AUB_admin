<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Teacher extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'first_name',
        'last_name',
        'name',
        'email',
        'phone',
        'tax_code',
        'description',
        'photo_path',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classLessons(): BelongsToMany
    {
        return $this->belongsToMany(ClassLesson::class, 'class_lesson_teacher')
            ->withPivot('hours')
            ->withTimestamps();
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_teacher')
            ->withTimestamps();
    }

    public function displayName(): string
    {
        $name = trim((string) $this->name);
        if ($name !== '') {
            return $name;
        }

        $fullName = trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ])));

        return $fullName !== '' ? $fullName : ('Teacher #'.$this->id);
    }
}
