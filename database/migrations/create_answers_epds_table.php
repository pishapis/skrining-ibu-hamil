<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('answers_epds', function (Blueprint $table) {
            $table->id();
            // Tabel skrining_epds tidak disediakan; simpan sebagai FK longgar
            $table->unsignedBigInteger('epds_id')->nullable()->index();
            $table->text('jawaban')->nullable();
            $table->integer('score')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('answers_epds');
    }
};
