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
    Schema::create('doctor_profiles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')
              ->unique()                       // one profile per doctor
              ->constrained('users')
              ->cascadeOnDelete();

        $table->string('address')->nullable();
        $table->string('city')->nullable();
        $table->string('state')->nullable();
        $table->string('pincode', 10)->nullable();
        $table->string('specialization')->nullable();   // e.g. Orthodontist
        $table->string('qualification')->nullable();    // e.g. BDS, MDS
        $table->integer('experience_years')->nullable();
        $table->text('bio')->nullable();
        $table->string('photo')->nullable();            // file path in storage
        $table->string('consultation_fee')->nullable();
        $table->json('languages')->nullable();          // ["Hindi","English"]
        $table->json('available_days')->nullable();     // ["Mon","Tue","Wed"]
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('doctor_profiles');
}
};
