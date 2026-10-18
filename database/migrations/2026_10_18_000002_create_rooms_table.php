<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room', function (Blueprint $table) {
            $table->id('room_id');
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('appointment', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->after('assistant_id')->constrained('room', 'room_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointment', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_id');
        });

        Schema::dropIfExists('room');
    }
};
