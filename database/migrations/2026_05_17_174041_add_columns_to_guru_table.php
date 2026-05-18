<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guru', function (Blueprint $table) {
            $table->string('nip')->nullable()->after('nik');
            $table->string('nama_lembaga')->nullable()->after('nip');
            $table->string('jabatan')->nullable()->after('nama_lembaga');
            $table->string('surat_tugas')->nullable()->after('jabatan');
        });
    }

    public function down(): void
    {
        Schema::table('guru', function (Blueprint $table) {
            $table->dropColumn(['nip', 'nama_lembaga', 'jabatan', 'surat_tugas']);
        });
    }
};