<?php

// database/migrations/xxxx_add_session_to_hasil_epds.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('hasil_epds', function (Blueprint $table) {
            // jika epds_id saat ini NOT NULL, ubah jadi nullable
            $table->unsignedBigInteger('epds_id')->nullable()->change();

            $table->string('status')->default('draft')->index();          // draft | submitted
            $table->uuid('session_token')->nullable()->index();           // identitas session yang bisa diresume
            $table->string('trimester')->nullable()->index();             // trimester_1 | trimester_2 | trimester_3 | pasca_hamil
            $table->unsignedBigInteger('total_score')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();

            // menjaga 1 jawaban per pertanyaan per session
            $table->unique(['session_token','epds_id']);
        });
    }

    public function down(): void {
        Schema::table('hasil_epds', function (Blueprint $table) {
            $table->dropUnique(['session_token','epds_id']);
            $table->dropColumn(['status','session_token','trimester','total_score','started_at','submitted_at']);
        });
    }
};
