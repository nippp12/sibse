<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sampah_transaksi', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sampah_id')
                ->constrained('sampah')
                ->onDelete('cascade');

            $table->foreignId('pengepulan_id')
                ->nullable()
                ->constrained('pengepulan')
                ->onDelete('cascade');

            $table->enum('tipe', ['penambahan', 'pengurangan'])
                ->comment('Tipe transaksi untuk sampah, misal penambahan dari pengepulan atau pengurangan karena penjualan');

            $table->decimal('jumlah', 12, 2)
                ->comment('Jumlah kuantitas yang ditransaksikan');

            $table->string('deskripsi')
                ->nullable()
                ->comment('Deskripsi tambahan transaksi jika diperlukan');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sampah_transaksi');
    }
};
