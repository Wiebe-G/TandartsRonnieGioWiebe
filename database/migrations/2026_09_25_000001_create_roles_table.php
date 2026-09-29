<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role', function (Blueprint $table) {
            $table->id('role_id');
            $table->string('name')->unique();
        });

        // Every user needs a role, so the roles are part of the schema itself.
        DB::table('role')->insert([
            ['name' => 'customer'],
            ['name' => 'dentist'],
            ['name' => 'assistent'],
            ['name' => 'admin']
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('role');
    }
};
