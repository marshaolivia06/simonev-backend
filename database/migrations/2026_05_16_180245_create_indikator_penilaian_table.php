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
    Schema::create('indikator_penilaian', function (Blueprint $table) {
        $table->id('id_indikator');
        $table->foreignId('id_aspek')->constrained('aspek_perkembangan', 'id_aspek')->cascadeOnDelete();
        $table->string('nama_indikator');
        $table->string('nama_kegiatan')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indikator_penilaian');
    }
};
