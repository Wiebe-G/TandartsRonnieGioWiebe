<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee', function (Blueprint $table) {
            $table->id('employee_id');
            $table->foreignId('role_id')->constrained('role', 'role_id')->restrictOnDelete();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phonenumber', 20)->nullable();
            $table->date('birthday')->nullable();
            $table->string('status')->default('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee');
    }
};
