<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademyRoom extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'academy_building_id',
        'name',
        'slug',
        'capacity',
        'room_type',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(AcademyBuilding::class, 'academy_building_id');
    }

    public function scheduledLessons(): HasMany
    {
        return $this->hasMany(ScheduledLesson::class);
    }
}
