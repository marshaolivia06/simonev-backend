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
    Schema::table('anak', function (Blueprint $table) {
        $table->string('jenis_kelamin')->nullable()->change();
    });

    Schema::table('guru', function (Blueprint $table) {
        $table->string('jenis_kelamin')->nullable()->change();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
