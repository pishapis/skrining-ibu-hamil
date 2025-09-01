<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('data_diri', function (Blueprint $table) {
            $table->id();

            // Relasi user bawaan Laravel (opsional)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('nama');
            $table->string('nik', 32)->unique();

            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();

            $table->string('pendidikan_terakhir')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('agama')->nullable();
            $table->string('golongan_darah', 3)->nullable();

            $table->text('alamat_rumah')->nullable();

            // Kode wilayah (menyelaraskan dengan model)
            $table->string('kode_prov', 20)->nullable()->index();
            $table->string('kode_kab', 20)->nullable()->index();
            $table->string('kode_kec', 20)->nullable()->index();
            $table->string('kode_des', 20)->nullable()->index();

            $table->string('no_telp', 30)->nullable();
            $table->string('no_jkn', 30)->nullable();

            // Relasi layanan kesehatan
            $table->unsignedBigInteger('puskesmas_id')->nullable()->index(); // table 'puskesmas' belum disediakan di sini
            $table->foreignId('faskes_rujukan_id')
                  ->nullable()
                  ->constrained('fasilitas_kesehatan_rujukan')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('data_diri');
    }
};
