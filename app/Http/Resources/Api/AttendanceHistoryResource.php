<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->resource;

        return [
            'student' => [
                'id' => $payload['student']['id'],
                'display_name' => $payload['student']['display_name'],
            ],
            'period' => [
                'month' => $payload['period']['month'],
                'starts_on' => $payload['period']['starts_on'],
                'ends_on' => $payload['period']['ends_on'],
            ],
            'summary' => [
                'marked' => $payload['summary']['marked'],
                'present' => $payload['summary']['present'],
                'absent' => $payload['summary']['absent'],
                'excused' => $payload['summary']['excused'],
            ],
            'records' => array_map(
                static fn (array $record): array => [
                    'id' => $record['id'],
                    'date' => $record['date'],
                    'starts_at' => $record['starts_at'],
                    'ends_at' => $record['ends_at'],
                    'status' => $record['status'],
                    'lesson' => $record['lesson'],
                    'title' => $record['title'],
                    'teacher' => $record['teacher'],
                    'location' => $record['location'],
                ],
                $payload['records'],
            ),
        ];
    }
}
