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
    // Doctor sets which days and times they are available
    Schema::create('doctor_schedules', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // the doctor
        $table->tinyInteger('day_of_week');   // 1=Mon, 2=Tue ... 6=Sat, 0=Sun
        $table->time('start_time');           // e.g. 09:00:00
        $table->time('end_time');             // e.g. 09:30:00
        $table->boolean('is_active')->default(true);
        $table->timestamps();

        $table->unique(['user_id', 'day_of_week', 'start_time'], 'unique_doctor_slot');
        $table->index(['user_id', 'day_of_week']);
    });

    // Specific dates doctor is NOT available
    Schema::create('blocked_dates', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->date('blocked_date');
        $table->string('reason')->nullable();
        $table->timestamps();

        $table->unique(['user_id', 'blocked_date']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_schedules');
    }
};
