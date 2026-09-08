<?php

namespace App\Http\Resources\Api;

use App\Models\AcademyParent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AcademyParent
 */
class ParentProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->loadMissing('students.academyClasses');

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'display_name' => $this->displayName(),
            'children' => ParentChildResource::collection($this->students)->resolve(),
        ];
    }
}
