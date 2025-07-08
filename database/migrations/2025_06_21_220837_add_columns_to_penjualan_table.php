<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            // Tambahkan foreign key untuk KasTransaksi
            if (!Schema::hasColumn('penjualan', 'kas_transaksi_id')) {
                // Posisikan setelah petugas_id
                $table->foreignId('kas_transaksi_id')->nullable()->constrained('kas_transaksi')->onDelete('set null')->after('petugas_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            if (Schema::hasColumn('penjualan', 'kas_transaksi_id')) {
                $table->dropForeign(['kas_transaksi_id']);
                $table->dropColumn('kas_transaksi_id');
            }
        });
    }
};