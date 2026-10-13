<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_treatment', function (Blueprint $table) {
            $table->foreignId('appointment_id')->constrained('appointment', 'appointment_id')->cascadeOnDelete();
            $table->foreignId('treatment_id')->constrained('treatment', 'treatment_id')->restrictOnDelete();

            $table->primary(['appointment_id', 'treatment_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_treatment');
    }
};
