<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postcode', function (Blueprint $table) {
            $table->string('address')->primary();
            $table->string('street');
            $table->string('residence');
            $table->string('house_number', 10);
            $table->string('city');
			$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postcode');
    }
};
