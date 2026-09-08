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
}
