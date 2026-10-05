<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice', function (Blueprint $table) {
            $table->id('invoice_id');
            $table->foreignId('appointment_id')->constrained('appointment', 'appointment_id')->restrictOnDelete();
            $table->date('invoicedate');
            $table->date('expiration_date');
			$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice');
    }
};
