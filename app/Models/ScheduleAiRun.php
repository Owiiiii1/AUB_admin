<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleAiRun extends Model
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_PARTIAL_CONFIRMATION = 'needs_partial_confirmation';

    public const STATUS_ERROR = 'error';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'schedule_week_id',
        'user_id',
        'status',
        'mode',
        'locale',
        'allow_partial',
        'prompt',
        'preferences',
        'metrics',
        'report',
        'warnings',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allow_partial' => 'boolean',
            'preferences' => 'array',
            'metrics' => 'array',
            'warnings' => 'array',
        ];
    }

    public function week(): BelongsTo
    {
        return $this->belongsTo(ScheduleWeek::class, 'schedule_week_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
