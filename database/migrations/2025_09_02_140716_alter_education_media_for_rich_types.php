<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('education_media', function (Blueprint $table) {
            $table->string('media_type')->default('image')->index(); // image|video|embed
            $table->string('poster_path')->nullable();   // poster utk video
            $table->string('external_url')->nullable();  // utk embed (youtube/vimeo)
            $table->string('caption')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedInteger('duration')->nullable(); // detik (opsional)
        });
    }
    public function down(): void
    {
        Schema::table('education_media', function (Blueprint $table) {
            $table->dropColumn(['media_type', 'poster_path', 'external_url', 'caption', 'mime', 'duration']);
        });
    }
};
