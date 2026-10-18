<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invoices can now be sent: the amount is fixed when the invoice is made
 * (later price changes do not change it), and sent_at records when it was emailed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice', function (Blueprint $table) {
            $table->decimal('amount', 8, 2)->default(0)->after('expiration_date');
            $table->timestamp('sent_at')->nullable()->after('amount');
            // One invoice per appointment
            $table->unique('appointment_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoice', function (Blueprint $table) {
            $table->dropUnique(['appointment_id']);
            $table->dropColumn(['amount', 'sent_at']);
        });
    }
};
