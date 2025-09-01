<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('skrining_dass', function (Blueprint $table) {
            $table->id();
            $table->longText('pertanyaan');
            $table->string('dimensi', 50); // contoh: "depresi", "ansietas", "stres"
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('skrining_dass');
    }
};
