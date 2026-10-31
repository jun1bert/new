<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scheduling_settings', function (Blueprint $table) {
            $table->id();
            $table->json('hours')->nullable();
        });
        DB::table('scheduling_settings')->insert(['id' => 1, 'hours' => null]);
        Schema::create('staff_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('hours');
        });
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('provider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->index(['provider_id', 'status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['provider_id']);
            $table->dropIndex(['provider_id', 'status', 'scheduled_at']);
            $table->dropColumn(['provider_id', 'duration_minutes']);
        });
        Schema::dropIfExists('staff_schedules');
        Schema::dropIfExists('scheduling_settings');
    }
};
