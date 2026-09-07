<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
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
        'student_email',
        'student_phone',
        'parent_phone',
        'parent_email',
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
        'father_first_name',
        'father_last_name',
        'father_phone',
        'father_email',
        'father_notes',
        'mother_first_name',
        'mother_last_name',
        'mother_phone',
        'mother_email',
        'mother_notes',
        'student_notes',
        'email',
        'phone',
        'address',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'form_filled_at' => 'date',
            'medical_certificate_expiry' => 'date',
            'is_existing_student' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
