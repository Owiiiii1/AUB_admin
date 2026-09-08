<?php

namespace App\Http\Resources\Api;

use App\Models\AcademyParent;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class MeResource extends JsonResource
{
    /**
     * @return array{user: array<string, mixed>, profile: array<string, mixed>|null}
     */
    public function toArray(Request $request): array
    {
        $profile = $this->matchingActorProfile();

        return [
            'user' => (new UserResource($this->resource))->resolve(),
            'profile' => match (true) {
                $profile instanceof Student => (new StudentProfileResource($profile))->resolve(),
                $profile instanceof AcademyParent => (new ParentProfileResource($profile))->resolve(),
                $profile instanceof Teacher => (new TeacherProfileResource($profile))->resolve(),
                default => null,
            },
        ];
    }
}
