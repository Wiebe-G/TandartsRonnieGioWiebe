<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointment_treatment', function (Blueprint $table) {
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_id')->constrained()->restrictOnDelete();

            $table->primary(['appointment_id', 'treatment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_treatment');
    }
};
