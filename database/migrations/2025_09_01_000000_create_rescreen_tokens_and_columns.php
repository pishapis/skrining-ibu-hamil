<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rescreen_tokens', function (Blueprint $table) {
            $table->integer('id', 11)->primary(); // UUID string
            $table->unsignedBigInteger('ibu_id');
            $table->integer('usia_hamil_id')->nullable();
            $table->enum('jenis', ['epds', 'dass']);
            $table->enum('trimester', ['trimester_1', 'trimester_2', 'trimester_3', 'pasca_hamil']);
            $table->unsignedBigInteger('issued_by')->nullable(); // users.id petugas
            $table->text('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedTinyInteger('max_uses')->default(1);
            $table->unsignedTinyInteger('used_count')->default(0);
            $table->enum('status', ['active', 'used', 'revoked'])->default('active');
            $table->timestamps();

            $table->foreign('ibu_id')->references('id')->on('data_diri')->cascadeOnDelete();
            $table->foreign('usia_hamil_id')->references('id')->on('usia_hamil')->nullOnDelete();
            $table->foreign('issued_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['ibu_id', 'jenis', 'trimester', 'status']);
        });

        Schema::table('hasil_epds', function (Blueprint $t) {
            $t->integer('rescreen_token_id', 11)->nullable()->after('session_token');
            $t->integer('batch_no')->default(1)->after('trimester');
            $t->foreign('rescreen_token_id')->references('id')->on('rescreen_tokens')->nullOnDelete();
            $t->index(['ibu_id', 'trimester', 'status', 'batch_no']);
        });

        Schema::table('hasil_dass', function (Blueprint $t) {
            $t->integer('rescreen_token_id', 11)->nullable()->after('session_token');
            $t->integer('batch_no')->default(1)->after('trimester');
            $t->foreign('rescreen_token_id')->references('id')->on('rescreen_tokens')->nullOnDelete();
            $t->index(['ibu_id', 'trimester', 'status', 'batch_no']);
        });
    }

    public function down(): void
    {
        Schema::table('hasil_epds', function (Blueprint $t) {
            $t->dropForeign(['rescreen_token_id']);
            $t->dropColumn(['rescreen_token_id', 'batch_no']);
        });
        Schema::table('hasil_dass', function (Blueprint $t) {
            $t->dropForeign(['rescreen_token_id']);
            $t->dropColumn(['rescreen_token_id', 'batch_no']);
        });
        Schema::dropIfExists('rescreen_tokens');
    }
};
