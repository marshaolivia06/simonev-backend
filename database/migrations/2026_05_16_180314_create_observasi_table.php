<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('observasi', function (Blueprint $table) {
        $table->id('id_observasi');
        $table->foreignId('id_guru')->constrained('guru', 'id_guru')->cascadeOnDelete();
        $table->foreignId('id_anak')->constrained('anak', 'id_anak')->cascadeOnDelete();
        $table->foreignId('id_indikator')->constrained('indikator_penilaian', 'id_indikator')->cascadeOnDelete();
        $table->string('semester');
        $table->date('tanggal');
        $table->string('nilai')->nullable();
        $table->text('komentar')->nullable();
        $table->string('foto')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('observasi');
    }
};
