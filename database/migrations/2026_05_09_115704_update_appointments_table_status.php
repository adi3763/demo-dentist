<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('appointments', function (Blueprint $table) {
        // Change status enum to include rejected
        $table->enum('status', [
            'pending',
            'confirmed',
            'rejected',
            'rescheduled',
            'completed',
            'cancelled',
        ])->default('pending')->change();

        // Reason when doctor rejects
        $table->text('rejected_reason')->nullable()->after('reschedule_reason');
        $table->timestamp('approved_at')->nullable()->after('rejected_reason');
        $table->timestamp('rejected_at')->nullable()->after('approved_at');
    });
}

public function down(): void
{
    Schema::table('appointments', function (Blueprint $table) {
        $table->dropColumn(['rejected_reason', 'approved_at', 'rejected_at']);
    });
}
};
