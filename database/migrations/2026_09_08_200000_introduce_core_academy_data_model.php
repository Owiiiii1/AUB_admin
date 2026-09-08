<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender')->nullable();
            $table->string('tax_code', 50)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('residence_address')->nullable();
            $table->string('residence_city_province')->nullable();
            $table->string('residence_postal_code', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('course_aa_2026_27')->nullable();
            $table->text('other_courses')->nullable();
            $table->boolean('is_existing_student')->default(false);
            $table->date('form_filled_at')->nullable();
            $table->date('medical_certificate_expiry')->nullable();
            $table->string('student_photo_path')->nullable();
            $table->string('parent_id_document_path')->nullable();
            $table->string('general_regulation_form_path')->nullable();
            $table->string('minor_entry_exit_form_path')->nullable();
            $table->string('rights_release_form_path')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('parents', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('tax_code', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('student_parent', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('parents')->cascadeOnDelete();
            $table->string('relation_type', 32)->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'parent_id']);
        });

        Schema::create('academy_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 16)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['course_id', 'sort_order']);
        });

        Schema::create('academy_class_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_class_id')->constrained('academy_classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->timestamps();

            $table->unique('student_id');
        });

        Schema::create('class_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('academy_class_id')->constrained('academy_classes')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->unsignedSmallInteger('hours')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['academic_year_id', 'academy_class_id', 'lesson_id'], 'class_lessons_year_class_lesson_unique');
        });

        Schema::create('class_lesson_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_lesson_id')->constrained('class_lessons')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->unsignedSmallInteger('hours')->nullable();
            $table->timestamps();

            $table->unique(['class_lesson_id', 'teacher_id']);
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->unique('user_id');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->after('customer_id')->constrained('students')->nullOnDelete();
        });

        $now = now();

        DB::table('academic_years')->insert([
            'name' => '2026/2027',
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-06-30',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->dropLegacyAcademyStudentTables();
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_id');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::dropIfExists('class_lesson_teacher');
        Schema::dropIfExists('class_lessons');
        Schema::dropIfExists('academy_class_student');
        Schema::dropIfExists('academy_classes');
        Schema::dropIfExists('student_parent');
        Schema::dropIfExists('parents');
        Schema::dropIfExists('students');
        Schema::dropIfExists('academic_years');
    }

    private function dropLegacyAcademyStudentTables(): void
    {
        if (Schema::hasTable('scheduled_lessons') && Schema::hasColumn('scheduled_lessons', 'course_group_id')) {
            DB::table('scheduled_lessons')->delete();

            Schema::table('scheduled_lessons', function (Blueprint $table) {
                if (Schema::getConnection()->getDriverName() === 'mysql') {
                    $table->dropForeign(['course_group_id']);
                    $table->dropIndex('sched_lessons_group_date_idx');
                }
                $table->dropColumn('course_group_id');
            });

            Schema::table('scheduled_lessons', function (Blueprint $table) {
                $table->foreignId('academy_class_id')->nullable()->after('academy_room_id')->constrained('academy_classes')->nullOnDelete();
                $table->index(['schedule_week_id', 'academy_class_id', 'lesson_date'], 'sched_lessons_class_date_idx');
            });
        }

        if (Schema::hasTable('activity_logs') && Schema::hasColumn('activity_logs', 'customer_id')) {
            DB::table('activity_logs')->update(['customer_id' => null]);
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'customer_id')) {
            DB::table('orders')->update(['customer_id' => null]);
        }

        if (Schema::hasTable('course_group_lesson')) {
            Schema::drop('course_group_lesson');
        }

        if (Schema::hasTable('course_group_customer')) {
            Schema::drop('course_group_customer');
        }

        if (Schema::hasTable('course_groups')) {
            Schema::drop('course_groups');
        }

        if (Schema::hasTable('customers')) {
            DB::table('customers')->delete();
        }
    }
};
