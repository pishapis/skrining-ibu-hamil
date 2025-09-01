<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('riwayat_kesehatan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ibu_id')
                  ->constrained('data_diri')
                  ->cascadeOnDelete();

            $table->unsignedSmallInteger('umur')->nullable();
            $table->unsignedSmallInteger('kehamilan_ke')->nullable();
            $table->unsignedSmallInteger('jml_anak_lahir_hidup')->nullable();
            $table->unsignedSmallInteger('riwayat_keguguran')->nullable();
            $table->json('riwayat_penyakit')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('riwayat_kesehatan');
    }
};
