<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('hasil_dass', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ibu_id')
                  ->constrained('data_diri')
                  ->cascadeOnDelete();

            // Tabel skrining_dass belum ada di kumpulan ini
            $table->unsignedBigInteger('dass_id')->nullable()->index();

            $table->foreignId('answers_dass_id')
                  ->nullable()
                  ->constrained('answers_dass')
                  ->nullOnDelete();

            $table->date('screening_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('hasil_dass');
    }
};
