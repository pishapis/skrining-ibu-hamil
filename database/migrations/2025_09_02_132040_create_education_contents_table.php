<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('education_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary')->nullable();
            $table->longText('body')->nullable(); // markdown/HTML ringan
            $table->string('cover_path')->nullable();

            // visibility: public, facility (by puskesmas), private (draft)
            $table->string('visibility')->default('public')->index();
            $table->unsignedBigInteger('puskesmas_id')->nullable()->index();

            $table->string('status')->default('draft')->index(); // draft|published
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedSmallInteger('reading_time')->default(0); // menit estimasi

            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('education_contents');
    }
};
