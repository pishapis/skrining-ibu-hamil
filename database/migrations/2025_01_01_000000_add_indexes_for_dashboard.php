<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hasil_epds', function (Blueprint $table) {
            $table->index(['ibu_id', 'status', 'session_token', 'screening_date'], 'epds_ibu_status_token_date_idx');
            $table->index(['trimester'], 'epds_trimester_idx');
        });

        Schema::table('hasil_dass', function (Blueprint $table) {
            $table->index(['ibu_id', 'status', 'session_token', 'screening_date'], 'dass_ibu_status_token_date_idx');
        });

        Schema::table('usia_hamil', function (Blueprint $table) {
            $table->index(['ibu_id', 'created_at'], 'usia_ibu_created_idx');
            $table->index(['ibu_id', 'hpht', 'hpl'], 'usia_ibu_hpht_hpl_idx');
        });

        Schema::table('data_diri', function (Blueprint $table) {
            $table->index(['user_id'], 'datadiri_user_idx');
        });
    }

    public function down(): void
    {
        Schema::table('hasil_epds', function (Blueprint $table) {
            $table->dropIndex('epds_ibu_status_token_date_idx');
            $table->dropIndex('epds_trimester_idx');
        });
        Schema::table('hasil_dass', function (Blueprint $table) {
            $table->dropIndex('dass_ibu_status_token_date_idx');
        });
        Schema::table('usia_hamil', function (Blueprint $table) {
            $table->dropIndex('usia_ibu_created_idx');
            $table->dropIndex('usia_ibu_hpht_hpl_idx');
        });
        Schema::table('data_diri', function (Blueprint $table) {
            $table->dropIndex('datadiri_user_idx');
        });
    }
};
