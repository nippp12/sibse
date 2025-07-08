<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cek dan drop foreign key serta kolom lama secara manual
        if (Schema::hasColumn('sampah_transaksi', 'pengepulan_id')) {
            Schema::table('sampah_transaksi', function (Blueprint $table) {
                $table->dropForeign(['pengepulan_id']);
                $table->dropColumn('pengepulan_id');
            });
        }

        if (Schema::hasColumn('sampah_transaksi', 'penjualan_id')) {
            Schema::table('sampah_transaksi', function (Blueprint $table) {
                $table->dropForeign(['penjualan_id']);
                $table->dropColumn('penjualan_id');
            });
        }

        // Tambahkan kolom morph baru
        Schema::table('sampah_transaksi', function (Blueprint $table) {
            $table->nullableMorphs('transactable'); // ini akan buat transactable_id dan transactable_type
        });
    }

    public function down(): void
    {
        Schema::table('sampah_transaksi', function (Blueprint $table) {
            $table->dropMorphs('transactable');

            $table->foreignId('pengepulan_id')->nullable()->constrained('pengepulan')->onDelete('cascade');
            $table->foreignId('penjualan_id')->nullable()->constrained('penjualan')->onDelete('cascade');
        });
    }
};
