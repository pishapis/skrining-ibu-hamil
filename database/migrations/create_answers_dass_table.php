<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('answers_dass', function (Blueprint $table) {
            $table->id();
            $table->text('jawaban')->nullable();
            $table->integer('score')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('answers_dass');
    }
};
