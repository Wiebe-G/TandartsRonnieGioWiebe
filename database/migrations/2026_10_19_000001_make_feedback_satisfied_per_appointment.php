<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One feedback design for everyone: a customer says whether they were satisfied with an appointment
 * (Ronnie's customer screens), and the dentist is kept alongside for the practice statistics.
 *
 * Works on both earlier versions of the table: the 1-5 rating version and the satisfied version.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('feedback', 'satisfied')) {
            Schema::table('feedback', function (Blueprint $table) {
                $table->boolean('satisfied')->default(true)->after('customer_id'); // tevreden / niet tevreden
            });
        }

        if (Schema::hasColumn('feedback', 'rating')) {
            // 4 or 5 stars counted as satisfied before
            DB::table('feedback')->update(['satisfied' => DB::raw('rating >= 4')]);

            Schema::table('feedback', function (Blueprint $table) {
                $table->dropColumn('rating');
            });
        }

        if (! Schema::hasColumn('feedback', 'dentist_id')) {
            Schema::table('feedback', function (Blueprint $table) {
                $table->foreignId('dentist_id')->nullable()->after('customer_id')->constrained('users')->cascadeOnDelete();
            });

            DB::table('feedback')->update([
                'dentist_id' => DB::raw('(select dentist_id from appointment where appointment.appointment_id = feedback.appointment_id)'),
            ]);
        }

        // Feedback without an appointment cannot exist any more: one review per appointment
        DB::table('feedback')->whereNull('appointment_id')->delete();

        $hasUnique = collect(Schema::getIndexes('feedback'))
            ->contains(fn (array $index) => $index['unique'] && $index['columns'] === ['appointment_id']);

        Schema::table('feedback', function (Blueprint $table) use ($hasUnique) {
            // The review goes when the appointment goes
            $table->dropForeign(['appointment_id']);
            $table->foreign('appointment_id')->references('appointment_id')->on('appointment')->cascadeOnDelete();

            if (! $hasUnique) {
                $table->unique('appointment_id');
            }
        });
    }

    public function down(): void
    {
        // Keeps the satisfied version; going back to star ratings would lose information
    }
};
