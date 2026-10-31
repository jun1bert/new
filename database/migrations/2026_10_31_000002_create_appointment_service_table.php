<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consent_forms', fn (Blueprint $table) => $table->text('service_name')->nullable()->change());
        Schema::create('appointment_service', function (Blueprint $table) {
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('session_count');
            $table->primary(['appointment_id', 'service_id']);
        });
        DB::table('appointments')->join('services', 'services.id', '=', 'appointments.service_id')
            ->select('appointments.id', 'appointments.service_id', 'services.session_count')
            ->orderBy('appointments.id')->chunk(500, function ($appointments) {
                DB::table('appointment_service')->insert($appointments->map(fn ($appointment) => [
                    'appointment_id' => $appointment->id, 'service_id' => $appointment->service_id,
                    'session_count' => $appointment->session_count,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_service');
    }
};
