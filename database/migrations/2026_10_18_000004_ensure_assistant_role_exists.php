<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The roles migration already creates "assistant" (as id 4), but databases where it
 * was removed afterwards need it back. Id 4 is reused when it is free, so every
 * database ends up with the same role ids.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('role')->where('name', 'assistant')->exists()) {
            return;
        }

        $role = ['name' => 'assistant', 'created_at' => now(), 'updated_at' => now()];

        if (! DB::table('role')->where('role_id', 4)->exists()) {
            $role['role_id'] = 4;
        }

        DB::table('role')->insert($role);
    }

    public function down(): void
    {
        // Left in place: the roles migration owns this role.
    }
};
