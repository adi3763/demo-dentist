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
    Schema::create('password_reset_otps', function (Blueprint $table) {
        $table->id();
        $table->string('email');
        $table->string('otp', 6);               // 6-digit OTP
        $table->timestamp('expires_at');        // valid for 15 minutes
        $table->boolean('used')->default(false);
        $table->timestamps();

        $table->index('email');
    });
}

public function down(): void
{
    Schema::dropIfExists('password_reset_otps');
}
};
