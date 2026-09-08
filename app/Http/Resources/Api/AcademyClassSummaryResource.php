<?php

namespace App\Http\Resources\Api;

use App\Models\AcademyClass;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademyClassSummaryResource extends JsonResource
{
    /**
     * @return array{id: int, name: string}|null
     */
    public function toArray(Request $request): array
    {
        /** @var AcademyClass $class */
        $class = $this->resource;

        return [
            'id' => $class->id,
            'name' => $class->name,
        ];
    }
}
