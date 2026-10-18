<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An earlier version opened "/" logged in as the first demo customer (Jan). That code is gone,
 * but browsers kept those sessions and still started as Jan. This logs everyone out once.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->delete();
        }
    }

    public function down(): void
    {
        // Nothing to restore
    }
};
