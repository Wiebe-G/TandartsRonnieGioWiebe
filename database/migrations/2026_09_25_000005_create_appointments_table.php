<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment', function (Blueprint $table) {
            $table->id('appointment_id');
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('dentist_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assistant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date');
            $table->time('starttime');
            $table->time('endtime');
            $table->string('status')->default('scheduled');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment');
    }
};
