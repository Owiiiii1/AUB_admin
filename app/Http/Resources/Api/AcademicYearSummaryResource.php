<?php

namespace App\Http\Resources\Api;

use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicYearSummaryResource extends JsonResource
{
    /**
     * @return array{id: int, name: string}
     */
    public function toArray(Request $request): array
    {
        /** @var AcademicYear $year */
        $year = $this->resource;

        return [
            'id' => $year->id,
            'name' => $year->name,
        ];
    }
}
