<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fasilitas_kesehatan_rujukan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('alamat')->nullable();
            // Nama wilayah (opsional)
            $table->string('kec')->nullable();
            $table->string('kota')->nullable();
            $table->string('prov')->nullable();
            // Kode wilayah
            $table->string('kode_kec', 20)->nullable()->index();
            $table->string('kode_kota', 20)->nullable()->index();
            // Di model: belongsTo(Provinsi::class, 'kode_prov', 'id') -> integer
            $table->unsignedBigInteger('kode_prov')->nullable()->index();
            $table->string('no_telp', 30)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('fasilitas_kesehatan_rujukan');
    }
};
