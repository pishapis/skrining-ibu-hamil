<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // MySQL
        DB::statement('ALTER TABLE education_media MODIFY path VARCHAR(255) NULL');
    }

    public function down(): void
    {
        // Kembalikan ke NOT NULL jika perlu (opsional)
        DB::statement('ALTER TABLE education_media MODIFY path VARCHAR(255) NOT NULL');
    }
};
