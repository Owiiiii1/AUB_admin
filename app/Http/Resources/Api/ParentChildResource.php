<?php

namespace App\Http\Resources\Api;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Student
 */
class ParentChildResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $class = $this->academyClass();
        $photo = $this->profilePhoto();

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'display_name' => $this->displayName(),
            'photo_url' => $photo === null ? null : \App\Support\SecureFileUrl::api($photo),
            'academy_class' => $class !== null ? (new AcademyClassSummaryResource($class))->resolve() : null,
        ];
    }
}
