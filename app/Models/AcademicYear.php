<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function classLessons(): HasMany
    {
        return $this->hasMany(ClassLesson::class);
    }

    public static function current(): ?self
    {
        return static::query()->where('is_active', true)->orderByDesc('starts_at')->first()
            ?? static::query()->orderByDesc('starts_at')->first();
    }
}
