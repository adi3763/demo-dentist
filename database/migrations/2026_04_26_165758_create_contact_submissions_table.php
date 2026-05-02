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
    Schema::create('contact_submissions', function (Blueprint $table) {
        $table->id();

        // Form fields
        $table->string('name');
        $table->string('email');
        $table->string('phone', 20);
        $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
        $table->text('message')->nullable();       // optional

        // Auto-captured by server
        $table->string('ip_address', 45)->nullable();

        // Admin workflow
        $table->enum('status', ['new', 'read', 'replied'])->default('new');
        $table->timestamp('read_at')->nullable();

        $table->timestamps();

        $table->index('status');
        $table->index('created_at');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_submissions');
    }
};
