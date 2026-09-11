<?php

namespace App\Http\Resources\Api;

use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'display_name' => $this->displayName(),
            'photo_url' => $this->safePhotoUrl(),
            'phone' => $this->nullableString($this->phone),
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'residence_address' => $this->nullableString($this->residence_address),
            'residence_city_province' => $this->nullableString($this->residence_city_province),
            'residence_postal_code' => $this->nullableString($this->residence_postal_code),
            'academy_class' => $class !== null ? (new AcademyClassSummaryResource($class))->resolve() : null,
            'academic_year' => $year !== null ? (new AcademicYearSummaryResource($year))->resolve() : null,
        ];
    }

    private function safePhotoUrl(): ?string
    {
        $path = $this->student_photo_path;

        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
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
