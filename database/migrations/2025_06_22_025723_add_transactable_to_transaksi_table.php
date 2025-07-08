<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transaksi')) {
            Schema::table('transaksi', function (Blueprint $table) {
                // Tambahkan kolom polimorfik secara manual agar bisa menggunakan after()
                $table->unsignedBigInteger('transactable_id')->nullable()->after('deskripsi');
                $table->string('transactable_type')->nullable()->after('transactable_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transaksi')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropColumn(['transactable_id', 'transactable_type']);
            });
        }
    }
};
