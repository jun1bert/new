<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('skin_analysis_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('path');
            $table->timestamps();
            $table->unique(['appointment_id', 'service_id', 'type']);
        });
    }
    public function down(): void { Schema::dropIfExists('skin_analysis_photos'); }
};
