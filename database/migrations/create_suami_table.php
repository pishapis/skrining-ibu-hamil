<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('suami', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ibu_id')
                  ->constrained('data_diri')
                  ->cascadeOnDelete();

            $table->string('nama'); 
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('agama')->nullable();
            $table->string('no_telp', 30)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('suami');
    }
};
