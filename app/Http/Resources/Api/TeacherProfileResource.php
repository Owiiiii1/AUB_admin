<?php

namespace App\Http\Resources\Api;

use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Teacher
 */
class TeacherProfileResource extends JsonResource
{
    /**
     * @return array{id: int, first_name: string|null, last_name: string|null, display_name: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'display_name' => $this->displayName(),
        ];
    }
}
