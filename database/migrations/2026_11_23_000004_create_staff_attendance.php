<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamp('clocked_in_at');
            $table->timestamp('clocked_out_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'clocked_out_at']);
            $table->index('clocked_in_at');
        });
        Schema::create('attendance_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_attendance_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 500);
            $table->json('before')->nullable();
            $table->json('after');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('attendance_changes'); Schema::dropIfExists('staff_attendances'); }
};
