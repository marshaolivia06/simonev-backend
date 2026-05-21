<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('kelas', function (Blueprint $table) {
        $table->dropForeign(['id_guru']);
        $table->dropColumn('id_guru');
        $table->string('wali_kelas')->after('nama_kelas');
    });
}

public function down()
{
    Schema::table('kelas', function (Blueprint $table) {
        $table->dropColumn('wali_kelas');
        $table->unsignedBigInteger('id_guru')->after('nama_kelas');
        $table->foreign('id_guru')->references('id_guru')->on('guru');
    });
}
};
