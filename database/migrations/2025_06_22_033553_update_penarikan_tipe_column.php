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
        Schema::table('penarikan', function (Blueprint $table) {
            // Tambahkan foreign key untuk KasTransaksi
            // Ini akan menunjuk ke record KasTransaksi yang dibuat saat penarikan disetujui (pengeluaran kas)
            if (!Schema::hasColumn('penarikan', 'kas_transaksi_id')) {
                $table->foreignId('kas_transaksi_id')
                      ->nullable() // Bisa null jika penarikan belum disetujui atau dibatalkan
                      ->constrained('kas_transaksi')
                      ->onDelete('set null') // Jika KasTransaksi dihapus, ID di sini jadi null
                      ->after('tanggal_pengajuan');
            }

            // Tambahkan foreign key untuk Transaksi (saldo user)
            // Ini akan menunjuk ke record Transaksi yang dibuat saat penarikan disetujui (pengurangan saldo user)
            if (!Schema::hasColumn('penarikan', 'user_transaksi_id')) {
                $table->foreignId('user_transaksi_id')
                      ->nullable() // Bisa null jika penarikan belum disetujui atau dibatalkan
                      ->constrained('transaksi') // Constraints ke tabel 'transaksi' (saldo user)
                      ->onDelete('set null') // Jika Transaksi dihapus, ID di sini jadi null
                      ->after('kas_transaksi_id'); // Posisikan setelah kas_transaksi_id
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penarikan', function (Blueprint $table) {
            // Hapus foreign key dan kolomnya saat rollback
            if (Schema::hasColumn('penarikan', 'user_transaksi_id')) {
                $table->dropConstrainedForeignId('user_transaksi_id');
            }
            if (Schema::hasColumn('penarikan', 'kas_transaksi_id')) {
                $table->dropConstrainedForeignId('kas_transaksi_id');
            }
        });
    }
};
