<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('education_media', function (Blueprint $table) {
            $table->string('processing_status')->default('pending')->after('sort_order'); 
            // pending, processing, completed, failed
            $table->integer('processing_progress')->default(0)->after('processing_status');
            $table->text('processing_error')->nullable()->after('processing_progress');
        });
    }

    public function down(): void
    {
        Schema::table('education_media', function (Blueprint $table) {
            $table->dropColumn(['processing_status', 'processing_progress', 'processing_error']);
        });
    }
};