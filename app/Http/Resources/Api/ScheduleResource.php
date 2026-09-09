<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->resource;

        return [
            'student' => $payload['student'],
            'week' => $payload['week'],
            'days' => $payload['days'],
            'empty_reason' => $payload['empty_reason'],
        ];
    }
}
