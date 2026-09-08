<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('account_type', 32)->default('staff')->after('email');
            $table->boolean('is_active')->default(true)->after('account_type');
            $table->index('account_type');
        });

        DB::table('users')->update([
            'account_type' => 'staff',
            'is_active' => true,
        ]);

        if (! Schema::hasColumn('students', 'user_id')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
                $table->unique('user_id');
            });
        }

        if (! Schema::hasColumn('parents', 'user_id')) {
            Schema::table('parents', function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
                $table->unique('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('parents', 'user_id')) {
            Schema::table('parents', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('user_id');
            });
        }

        if (Schema::hasColumn('students', 'user_id')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('user_id');
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['account_type']);
            $table->dropColumn(['account_type', 'is_active']);
        });
    }
};
