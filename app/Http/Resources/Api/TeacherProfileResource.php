<?php

namespace App\Http\Resources\Api;

use App\Models\Teacher;
use App\Support\SecureFileUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Teacher
 */
class TeacherProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $photo = $this->profilePhoto();

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'display_name' => $this->displayName(),
            'photo_url' => $photo === null ? null : SecureFileUrl::api($photo),
            'photo' => $photo === null ? null : [
                'file_uuid' => $photo->uuid,
                'url' => SecureFileUrl::api($photo),
            ],
        ];
    }
}
