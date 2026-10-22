<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_claim_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claimant_id')->constrained('users')->cascadeOnDelete();
            $table->string('name_used');
            $table->date('appointment_date');
            $table->string('service_name');
            $table->string('booking_contact_number')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_claim_requests');
    }
};
