<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'discipline',
        'name',
        'sort_order',
        'study_starts_at',
        'study_ends_at',
    ];

    public function academyClasses(): HasMany
    {
        return $this->hasMany(AcademyClass::class)->orderBy('sort_order')->orderBy('name');
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_course')
            ->withTimestamps();
    }

    public function studyStartsAt(): ?string
    {
        return $this->formatStudyTime($this->attributes['study_starts_at'] ?? null);
    }

    public function studyEndsAt(): ?string
    {
        return $this->formatStudyTime($this->attributes['study_ends_at'] ?? null);
    }

    private function formatStudyTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr((string) $value, 0, 5);
    }
}
