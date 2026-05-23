<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengumuman', function (Blueprint $table) {
            $table->string('kategori')->default('Info')->after('tanggal');
        });
    }

    public function down(): void
    {
        Schema::table('pengumuman', function (Blueprint $table) {
            $table->dropColumn('kategori');
        });
    }
};