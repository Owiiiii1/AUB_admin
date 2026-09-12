<?php

namespace App\Http\Resources\Api;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Support\SecureFileUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Student
 */
class StudentProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $class = $this->academyClass();
        $year = AcademicYear::current();
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
            'phone' => $this->nullableString($this->phone),
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'residence_address' => $this->nullableString($this->residence_address),
            'residence_city_province' => $this->nullableString($this->residence_city_province),
            'residence_postal_code' => $this->nullableString($this->residence_postal_code),
            'academy_class' => $class !== null ? (new AcademyClassSummaryResource($class))->resolve() : null,
            'academic_year' => $year !== null ? (new AcademicYearSummaryResource($year))->resolve() : null,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
