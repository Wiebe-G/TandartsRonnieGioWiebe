<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('role')->insertOrIgnore(['name' => 'admin']);
    }

    public function down(): void
    {
        DB::table('role')->where('name', 'admin')->delete();
    }
};
