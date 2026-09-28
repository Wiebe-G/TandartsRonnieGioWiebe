<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer', function (Blueprint $table) {
            $table->id('customer_id');
            $table->foreignId('employee_id')->nullable()->constrained('employee', 'employee_id')->nullOnDelete();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->string('phonenumber', 20)->nullable();
            $table->date('birthday');
            $table->string('adress')->nullable();

            $table->foreign('adress')->references('adress')->on('postcode')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer');
    }
};
