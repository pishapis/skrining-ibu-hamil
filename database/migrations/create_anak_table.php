<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('anak', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ibu_id')
                  ->constrained('data_diri')
                  ->cascadeOnDelete();

            $table->string('nama');
            $table->string('nik', 32)->nullable()->unique();
            $table->date('tanggal_lahir')->nullable();
            // Sesuai kebiasaan: L/P
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('no_jkn', 30)->nullable();
            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('anak');
    }
};
