<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherAttendanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->resource;

        return [
            'lesson' => $payload['lesson'],
            'attendance_editable' => $payload['attendance_editable'],
            'reason' => $payload['reason'],
            'students' => $payload['students'],
        ];
    }
}
