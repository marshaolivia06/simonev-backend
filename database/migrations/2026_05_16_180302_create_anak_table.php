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
    Schema::create('anak', function (Blueprint $table) {
        $table->id('id_anak');
        $table->foreignId('id_kelas')->constrained('kelas', 'id_kelas')->cascadeOnDelete();
        $table->string('nama_anak');
        $table->string('id_orangtua')->nullable();
        $table->enum('jenis_kelamin', ['L', 'P']);
        $table->date('tanggal_lahir')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anak');
    }
};
