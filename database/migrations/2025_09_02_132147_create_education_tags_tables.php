<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('education_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('education_content_tag', function (Blueprint $table) {
            $table->foreignId('content_id')->constrained('education_contents')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('education_tags')->cascadeOnDelete();
            $table->primary(['content_id', 'tag_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('education_content_tag');
        Schema::dropIfExists('education_tags');
    }
};
