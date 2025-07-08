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
        Schema::table('penjualan', function (Blueprint $table) {
            // Tambahkan kolom 'status' setelah 'deskripsi'
            // Default 'pending' adalah pilihan yang aman untuk record yang sudah ada
            if (!Schema::hasColumn('penjualan', 'status')) {
                $table->enum('status', ['pending', 'diproses', 'selesai', 'dibatalkan'])->default('pending')->after('deskripsi');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            // Hapus kolom 'status' jika di-rollback
            if (Schema::hasColumn('penjualan', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};