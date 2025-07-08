<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        // 1. Drop kolom 'tanggal' jika ada
        if (Schema::hasColumn('kas_transaksi', 'tanggal')) {
            Schema::table('kas_transaksi', function (Blueprint $table) {
                $table->dropColumn('tanggal');
            });
        }

        // 2. Rename 'tipe_transaksi' ke 'tipe'
        if (Schema::hasColumn('kas_transaksi', 'tipe_transaksi')) {
            Schema::table('kas_transaksi', function (Blueprint $table) {
                $table->renameColumn('tipe_transaksi', 'tipe');
            });
        }

        // 3. Ubah tipe kolom 'tipe' menjadi enum dan tambahkan morph
        Schema::table('kas_transaksi', function (Blueprint $table) {
            if (Schema::hasColumn('kas_transaksi', 'tipe')) {
                $table->enum('tipe', ['pemasukan', 'pengeluaran'])->change();
            }
        
            // Buat kolom morph manual agar bisa pakai after()
            $table->unsignedBigInteger('transaksable_id')->nullable()->after('deskripsi');
            $table->string('transaksable_type')->nullable()->after('transaksable_id');
        });
        
    }

    public function down(): void
    {
        // 1. Drop morph
        Schema::table('kas_transaksi', function (Blueprint $table) {
            $table->dropMorphs('transaksable');
        });

        // 2. Ubah enum kembali jadi string
        Schema::table('kas_transaksi', function (Blueprint $table) {
            $table->string('tipe')->default('pemasukan')->change();
        });

        // 3. Rename kembali ke 'tipe_transaksi'
        Schema::table('kas_transaksi', function (Blueprint $table) {
            $table->renameColumn('tipe', 'tipe_transaksi');
        });

        // 4. Tambahkan kembali kolom tanggal
        Schema::table('kas_transaksi', function (Blueprint $table) {
            $table->timestamp('tanggal')->nullable()->useCurrent()->after('deskripsi');
        });
    }
};
