<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('treatment_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('treatment_course_id')->unique();
            $table->decimal('total', 10, 2);
            $table->decimal('opening_credit', 10, 2)->default(0);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->dropUnique(['appointment_id']);
            $table->foreign('appointment_id')->references('id')->on('appointments')->cascadeOnDelete();
            $table->foreignId('treatment_invoice_id')->nullable()->constrained()->restrictOnDelete();
        });
    }
    public function down(): void
    {
        throw new RuntimeException('Payment installments cannot safely be rolled back automatically.');
    }
};
