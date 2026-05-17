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
    Schema::create('guru', function (Blueprint $table) {
        $table->id('id_guru');
        $table->foreignId('id_user')->constrained('users')->cascadeOnDelete();
        $table->string('nik')->unique();
        $table->string('nama_guru');
        $table->string('no_telp')->nullable();
        $table->string('alamat')->nullable();
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
        Schema::dropIfExists('guru');
    }
};
