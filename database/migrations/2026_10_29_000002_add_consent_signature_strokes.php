<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consent_forms', fn (Blueprint $table) => $table->json('signature_strokes')->nullable());
    }

    public function down(): void
    {
        Schema::table('consent_forms', fn (Blueprint $table) => $table->dropColumn('signature_strokes'));
    }
};
