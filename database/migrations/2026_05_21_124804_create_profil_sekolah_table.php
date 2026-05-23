<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profil_sekolah', function (Blueprint $table) {
            $table->id();
            $table->string('nama_sekolah');
            $table->string('email')->nullable();
            $table->string('telepon')->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profil_sekolah');
    }
};