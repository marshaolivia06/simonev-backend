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
    Schema::table('orang_tua', function (Blueprint $table) {
        $table->date('tanggal_lahir_anak')->nullable()->after('kelas_anak');
    });
}

public function down()
{
    Schema::table('orang_tua', function (Blueprint $table) {
        $table->dropColumn('tanggal_lahir_anak');
    });
}
};
