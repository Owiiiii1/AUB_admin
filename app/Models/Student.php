<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'gender',
        'tax_code',
        'birth_date',
        'birth_place',
        'residence_address',
        'residence_city_province',
        'residence_postal_code',
        'email',
        'phone',
        'course_aa_2026_27',
        'other_courses',
        'is_existing_student',
        'form_filled_at',
        'medical_certificate_expiry',
        'student_photo_path',
        'parent_id_document_path',
        'general_regulation_form_path',
        'minor_entry_exit_form_path',
        'rights_release_form_path',
        'notes',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'form_filled_at' => 'date',
            'medical_certificate_expiry' => 'date',
            'is_existing_student' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(AcademyParent::class, 'student_parent', 'student_id', 'parent_id')
            ->withPivot('relation_type')
            ->withTimestamps();
    }

    public function academyClasses(): BelongsToMany
    {
        return $this->belongsToMany(AcademyClass::class, 'academy_class_student')
            ->withTimestamps();
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function academyClass(): ?AcademyClass
    {
        if ($this->relationLoaded('academyClasses')) {
            return $this->academyClasses->first();
        }

        return $this->academyClasses()->first();
    }

    public function parentOfType(string $type): ?AcademyParent
    {
        return $this->parents->first(
            static fn (AcademyParent $parent): bool => $parent->pivot?->relation_type === $type,
        );
    }

    public function displayName(): string
    {
        $name = trim((string) $this->name);
        if ($name !== '') {
            return $name;
        }

        $fullName = trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ])));

        return $fullName !== '' ? $fullName : ('Student #'.$this->id);
    }
}
