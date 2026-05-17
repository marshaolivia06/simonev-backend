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
    Schema::create('orang_tua', function (Blueprint $table) {
        $table->id('id_orangtua');
        $table->foreignId('id_user')->constrained('users')->cascadeOnDelete();
        $table->string('nik')->unique();
        $table->string('nama_orangtua');
        $table->string('no_telp')->nullable();
        $table->string('alamat')->nullable();
        $table->string('pekerjaan')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orang_tua');
    }
};
