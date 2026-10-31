<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('appointments')->whereIn('status', ['pending', 'scheduled'])
            ->whereIn('provider_id', DB::table('users')->select('id')->where('user_type', 'administrator'))
            ->update(['provider_id' => null]);
    }

    public function down(): void
    {
        // Previous assignments cannot be inferred after providers are reassigned.
    }
};
