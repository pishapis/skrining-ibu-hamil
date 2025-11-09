<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('generated_links', function (Blueprint $table) {
            $table->longText('qr_code')->nullable()->after('short_code');
        });
    }

    public function down()
    {
        Schema::table('generated_links', function (Blueprint $table) {
            $table->dropColumn('qr_code');
        });
    }
};