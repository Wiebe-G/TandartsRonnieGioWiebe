<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id('feedback_id');
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('dentist_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointment', 'appointment_id')->nullOnDelete();
            $table->unsignedTinyInteger('rating'); // 1 to 5
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
