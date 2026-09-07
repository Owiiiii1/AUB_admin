<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('tax_code')->nullable()->after('last_name');
            $table->date('birth_date')->nullable()->after('tax_code');
            $table->string('birth_place')->nullable()->after('birth_date');

            $table->string('residence_address')->nullable()->after('birth_place');
            $table->string('residence_city_province')->nullable()->after('residence_address');
            $table->string('residence_postal_code', 20)->nullable()->after('residence_city_province');
            $table->string('student_email')->nullable()->after('residence_postal_code');
            $table->string('student_phone', 50)->nullable()->after('student_email');

            $table->string('parent_phone', 50)->nullable()->after('student_phone');
            $table->string('parent_email')->nullable()->after('parent_phone');

            $table->string('course_aa_2026_27')->nullable()->after('parent_email');
            $table->text('other_courses')->nullable()->after('course_aa_2026_27');
            $table->boolean('is_existing_student')->default(false)->after('other_courses');
            $table->date('form_filled_at')->nullable()->after('is_existing_student');

            $table->date('medical_certificate_expiry')->nullable()->after('form_filled_at');
            $table->string('student_photo_path')->nullable()->after('medical_certificate_expiry');
            $table->string('parent_id_document_path')->nullable()->after('student_photo_path');
            $table->string('general_regulation_form_path')->nullable()->after('parent_id_document_path');
            $table->string('minor_entry_exit_form_path')->nullable()->after('general_regulation_form_path');
            $table->string('rights_release_form_path')->nullable()->after('minor_entry_exit_form_path');

            $table->string('father_first_name')->nullable()->after('rights_release_form_path');
            $table->string('father_last_name')->nullable()->after('father_first_name');
            $table->string('father_phone', 50)->nullable()->after('father_last_name');
            $table->string('father_email')->nullable()->after('father_phone');
            $table->text('father_notes')->nullable()->after('father_email');

            $table->string('mother_first_name')->nullable()->after('father_notes');
            $table->string('mother_last_name')->nullable()->after('mother_first_name');
            $table->string('mother_phone', 50)->nullable()->after('mother_last_name');
            $table->string('mother_email')->nullable()->after('mother_phone');
            $table->text('mother_notes')->nullable()->after('mother_email');

            $table->text('student_notes')->nullable()->after('mother_notes');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
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
            ]);
        });
    }
};
