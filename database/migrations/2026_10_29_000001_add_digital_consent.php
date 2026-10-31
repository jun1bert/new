<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('requires_consent')->default(false);
            $table->longText('consent_content')->nullable();
        });
        Schema::table('consent_forms', function (Blueprint $table) {
            $table->foreignId('appointment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('signed_name', 120)->nullable();
            $table->string('service_name', 120)->nullable();
            $table->longText('content_snapshot')->nullable();
            $table->string('content_hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('consent_forms', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->dropUnique(['appointment_id']);
            $table->dropColumn(['appointment_id', 'signed_name', 'service_name', 'content_snapshot', 'content_hash']);
        });
        Schema::table('services', fn (Blueprint $table) => $table->dropColumn(['requires_consent', 'consent_content']));
    }
};
