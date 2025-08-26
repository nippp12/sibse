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
        Schema::table('pengepulan_sampah', function (Blueprint $table) {
            // Asumsi kolom 'qty' adalah decimal(8, 2)
            // Ubah tipe dan default value sesuai struktur tabel Anda yang sebenarnya
            $table->decimal('qty', 8, 2)->default(0.00)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengepulan_sampah', function (Blueprint $table) {
            // Saat rollback, hapus default value
            $table->decimal('qty', 8, 2)->default(null)->change();
        });
    }
};