<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHargaToSampahTransaksiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sampah_transaksi', function (Blueprint $table) {
            $table->decimal('harga', 15, 2)->default(0)->after('jumlah')->comment('Harga saat transaksi');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sampah_transaksi', function (Blueprint $table) {
            $table->dropColumn('harga');
        });
    }
}
