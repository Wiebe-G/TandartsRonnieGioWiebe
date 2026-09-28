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
            $table->foreignId('customer_id')->constrained('customer', 'customer_id')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employee', 'employee_id')->restrictOnDelete();
            $table->foreignId('extra_employee_id')->nullable()->constrained('employee', 'employee_id')->nullOnDelete();
            $table->date('date');
            $table->time('starttime');
            $table->time('endtime');
            $table->string('status')->default('scheduled');
            $table->text('note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment');
    }
};
