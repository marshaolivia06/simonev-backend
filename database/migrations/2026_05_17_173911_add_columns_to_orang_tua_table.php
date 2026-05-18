<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orang_tua', function (Blueprint $table) {
            $table->string('hubungan')->nullable()->after('pekerjaan');
            $table->string('nama_anak')->nullable()->after('hubungan');
            $table->string('kelas_anak')->nullable()->after('nama_anak');
            $table->string('foto_ktp')->nullable()->after('kelas_anak');
        });
    }

    public function down(): void
    {
        Schema::table('orang_tua', function (Blueprint $table) {
            $table->dropColumn(['hubungan', 'nama_anak', 'kelas_anak', 'foto_ktp']);
        });
    }
};