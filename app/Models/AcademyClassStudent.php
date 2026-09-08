<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademyClassStudent extends Model
{
    protected $table = 'academy_class_student';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'academy_class_id',
        'student_id',
    ];

    public function academyClass(): BelongsTo
    {
        return $this->belongsTo(AcademyClass::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
