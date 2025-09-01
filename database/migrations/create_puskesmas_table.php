<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('puskesmas', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('alamat')->nullable();

            // label wilayah (optional)
            $table->string('kec')->nullable();
            $table->string('kota')->nullable();
            $table->string('prov')->nullable();

            // kode wilayah
            $table->string('kode_kec', 20)->nullable()->index();
            $table->string('kode_kota', 20)->nullable()->index();

            // relasi provinsi (model pakai 'kode_prov' -> provinces.id)
            $table->foreignId('kode_prov')->nullable()
                  ->constrained('indonesia_provinces')
                  ->nullOnDelete();

            // relasi ke faskes rujukan (opsional)
            $table->foreignId('faskes_rujukan_id')->nullable()
                  ->constrained('fasilitas_kesehatan_rujukan')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('puskesmas');
    }
};
