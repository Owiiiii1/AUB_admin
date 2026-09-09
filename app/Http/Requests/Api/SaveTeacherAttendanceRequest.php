<?php

namespace App\Http\Requests\Api;

use App\Models\AttendanceRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveTeacherAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attendance' => ['required', 'array'],
            'attendance.*.student_id' => ['required', 'integer'],
            'attendance.*.status' => ['nullable', 'string', Rule::in(AttendanceRecord::STATUSES)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ids = collect($this->input('attendance', []))->pluck('student_id');
            if ($ids->count() !== $ids->unique()->count()) {
                $validator->errors()->add('attendance', 'Duplicate student_id values are not allowed.');
            }
        });
    }

    /**
     * @return list<array{student_id: int, status: string|null}>
     */
    public function rows(): array
    {
        $rows = [];
        foreach ($this->validated('attendance') as $row) {
            $rows[] = [
                'student_id' => (int) $row['student_id'],
                'status' => $row['status'] ?? null,
            ];
        }

        return $rows;
    }
}
