<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('course_group_customer')
            ->select('customer_id', DB::raw('MIN(id) as keep_id'))
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('course_group_customer')
                ->where('customer_id', $duplicate->customer_id)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        if ($this->hasIndex('course_group_customer_customer_id_discipline_unique')) {
            if (! $this->hasIndex('course_group_customer_customer_id_tmp_idx')) {
                DB::statement('ALTER TABLE course_group_customer ADD INDEX course_group_customer_customer_id_tmp_idx (customer_id)');
            }

            DB::statement('ALTER TABLE course_group_customer DROP INDEX course_group_customer_customer_id_discipline_unique');
        }

        if (! $this->hasUniqueIndexOnColumn('customer_id')) {
            DB::statement('ALTER TABLE course_group_customer ADD UNIQUE course_group_customer_customer_id_unique (customer_id)');
        }

        if ($this->hasIndex('course_group_customer_customer_id_tmp_idx')) {
            DB::statement('ALTER TABLE course_group_customer DROP INDEX course_group_customer_customer_id_tmp_idx');
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('course_group_customer_customer_id_unique')) {
            Schema::table('course_group_customer', function (Blueprint $table) {
                $table->dropUnique(['customer_id']);
            });
        }

        if (! $this->hasIndex('course_group_customer_customer_id_discipline_unique')) {
            Schema::table('course_group_customer', function (Blueprint $table) {
                $table->unique(['customer_id', 'discipline']);
            });
        }
    }

    private function hasIndex(string $name): bool
    {
        return collect(DB::select('SHOW INDEX FROM course_group_customer'))
            ->contains(static fn ($index): bool => $index->Key_name === $name);
    }

    private function hasUniqueIndexOnColumn(string $column): bool
    {
        return collect(DB::select('SHOW INDEX FROM course_group_customer'))
            ->contains(static fn ($index): bool => $index->Column_name === $column
                && (int) $index->Non_unique === 0
                && $index->Key_name !== 'PRIMARY'
                && $index->Key_name !== 'course_group_customer_course_group_id_customer_id_unique');
    }
};
