<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->string('nama_kepala_sekolah')->nullable()->after('alamat');
            $table->string('nip_kepala_sekolah')->nullable()->after('nama_kepala_sekolah');
            $table->string('foto_ttd_ks')->nullable()->after('nip_kepala_sekolah');
        });
    }

    public function down(): void
    {
        Schema::table('profil_sekolah', function (Blueprint $table) {
            $table->dropColumn(['nama_kepala_sekolah', 'nip_kepala_sekolah', 'foto_ttd_ks']);
        });
    }
};