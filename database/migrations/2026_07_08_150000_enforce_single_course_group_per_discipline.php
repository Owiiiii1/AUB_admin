<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_group_customer', function (Blueprint $table) {
            $table->string('discipline', 20)->nullable()->after('customer_id');
        });

        $assignments = DB::table('course_group_customer as cgc')
            ->join('course_groups as cg', 'cg.id', '=', 'cgc.course_group_id')
            ->join('courses as c', 'c.id', '=', 'cg.course_id')
            ->select('cgc.id', 'c.discipline')
            ->get();

        foreach ($assignments as $assignment) {
            DB::table('course_group_customer')
                ->where('id', $assignment->id)
                ->update(['discipline' => $assignment->discipline]);
        }

        $duplicates = DB::table('course_group_customer')
            ->select('customer_id', 'discipline', DB::raw('MIN(id) as keep_id'))
            ->whereNotNull('discipline')
            ->groupBy('customer_id', 'discipline')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('course_group_customer')
                ->where('customer_id', $duplicate->customer_id)
                ->where('discipline', $duplicate->discipline)
                ->where('id', '!=', $duplicate->keep_id)
                ->delete();
        }

        Schema::table('course_group_customer', function (Blueprint $table) {
            $table->unique(['customer_id', 'discipline']);
        });
    }

    public function down(): void
    {
        Schema::table('course_group_customer', function (Blueprint $table) {
            $table->dropUnique(['customer_id', 'discipline']);
            $table->dropColumn('discipline');
        });
    }
};
