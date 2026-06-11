<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah kolom keterangan_aspek (JSON) ke tabel observasi_sesi
 * (atau bisa ke tabel observasi langsung jika tidak ada tabel sesi)
 *
 * keterangan_aspek disimpan sebagai JSON di satu baris sesi penilaian,
 * contoh value: {"1": "Anak mulai mampu...", "2": "Sudah aktif bergerak..."}
 * key = id_aspek, value = teks keterangan dari guru
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Pilihan A: jika ada tabel observasi_sesi (batch) ─────────────
        // Uncomment blok ini jika kamu pakai tabel sesi terpisah
        /*
        if (Schema::hasTable('observasi_sesi')) {
            Schema::table('observasi_sesi', function (Blueprint $table) {
                $table->json('keterangan_aspek')->nullable()->after('komentar');
            });
        }
        */

        // ── Pilihan B: kolom di tabel observasi langsung ─────────────────
        // keterangan_aspek akan sama untuk semua baris observasi
        // dalam satu sesi (id_anak + tanggal + semester yang sama)
        if (!Schema::hasColumn('observasi', 'keterangan_aspek')) {
            Schema::table('observasi', function (Blueprint $table) {
                $table->json('keterangan_aspek')->nullable()->after('komentar');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('observasi', 'keterangan_aspek')) {
            Schema::table('observasi', function (Blueprint $table) {
                $table->dropColumn('keterangan_aspek');
            });
        }

        /*
        if (Schema::hasColumn('observasi_sesi', 'keterangan_aspek')) {
            Schema::table('observasi_sesi', function (Blueprint $table) {
                $table->dropColumn('keterangan_aspek');
            });
        }
        */
    }
};
