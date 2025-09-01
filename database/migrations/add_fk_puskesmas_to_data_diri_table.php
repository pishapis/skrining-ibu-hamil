<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('data_diri', function (Blueprint $table) {
            if (Schema::hasColumn('data_diri', 'puskesmas_id')) {
                // hapus index lama jika ada, lalu buat FK
                try { $table->dropIndex(['puskesmas_id']); } catch (\Throwable $e) {}
                $table->foreign('puskesmas_id')->references('id')->on('puskesmas')->nullOnDelete();
            }
        });
    }

    public function down(): void {
        Schema::table('data_diri', function (Blueprint $table) {
            try { $table->dropForeign(['puskesmas_id']); } catch (\Throwable $e) {}
            $table->index('puskesmas_id');
        });
    }
};
