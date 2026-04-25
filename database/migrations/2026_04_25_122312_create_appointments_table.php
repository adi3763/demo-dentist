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
    Schema::create('appointments', function (Blueprint $table) {
        $table->id();

        // Doctor this appointment is with
        $table->foreignId('doctor_id')
              ->constrained('users')
              ->cascadeOnDelete();

        // Patient info — no login needed, stored directly
        $table->string('patient_name');
        $table->string('patient_phone', 20);
        $table->string('patient_email')->nullable();

        $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();

        // The booked slot
        $table->date('appointment_date');
        $table->time('start_time');
        $table->time('end_time');

        $table->enum('status', [
            'pending',
            'confirmed',
            'completed',
            'cancelled',
            'rescheduled',
        ])->default('pending');

        $table->text('patient_notes')->nullable();

        // For emergency reschedule by doctor
        $table->date('rescheduled_date')->nullable();
        $table->time('rescheduled_start_time')->nullable();
        $table->time('rescheduled_end_time')->nullable();
        $table->text('reschedule_reason')->nullable();

        // Track notification status
        $table->boolean('reminder_sent')->default(false);

        $table->timestamps();
        $table->softDeletes();

        // THE CONFLICT PREVENTION — no two bookings same doctor, date, time
        $table->unique(
            ['doctor_id', 'appointment_date', 'start_time'],
            'unique_appointment_slot'
        );
        $table->index(['appointment_date', 'status']);
        $table->index(['doctor_id', 'appointment_date']);
    });

}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
