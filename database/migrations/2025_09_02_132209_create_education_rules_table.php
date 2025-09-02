<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('education_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('education_contents')->cascadeOnDelete();

            // 'epds' atau 'dass'
            $table->string('screening_type'); // epds|dass

            // dimensi skor: epds_total | dass_dep | dass_anx | dass_str
            $table->string('dimension');

            $table->integer('min_score')->nullable();
            $table->integer('max_score')->nullable();

            // filter opsional berdasarkan trimester
            $table->string('trimester')->nullable(); // trimester_1|2|3|pasca_hamil
            $table->timestamps();

            $table->index(['screening_type', 'dimension']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('education_rules');
    }
};
