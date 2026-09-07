<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_ai_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_week_id')->constrained('schedule_weeks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 64);
            $table->string('mode', 32);
            $table->string('locale', 8)->nullable();
            $table->boolean('allow_partial')->default(false);
            $table->text('prompt')->nullable();
            $table->json('preferences')->nullable();
            $table->json('metrics')->nullable();
            $table->text('report')->nullable();
            $table->json('warnings')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['schedule_week_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_ai_runs');
    }
};
