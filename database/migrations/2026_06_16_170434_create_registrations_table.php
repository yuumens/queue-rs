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
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('polyclinic_id')->constrained();
            $table->foreignId('doctor_id')->constrained();
            $table->string('queue_number', 10);
            $table->unsignedSmallInteger('queue_sequence');
            $table->date('registration_date');
            $table->timestamps();
            $table->unique(['polyclinic_id', 'queue_sequence', 'registration_date'], 'reg_poly_seq_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
