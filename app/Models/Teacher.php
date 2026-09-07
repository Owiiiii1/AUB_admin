<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_teacher')
            ->withTimestamps();
    }
}
