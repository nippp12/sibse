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
        Schema::table('penjualan_sampah', function (Blueprint $table) {
            // Tambahkan kolom harga_per_unit
            // decimal(15, 2) adalah pilihan yang baik untuk mata uang
            $table->decimal('harga_per_unit', 15, 2)->after('qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penjualan_sampah', function (Blueprint $table) {
            // Hapus kolom harga_per_unit saat rollback
            $table->dropColumn('harga_per_unit');
        });
    }
};