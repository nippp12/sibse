<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pastikan tabel 'transaksi' ada dan kolom 'tipe' ada
        if (Schema::hasTable('transaksi') && Schema::hasColumn('transaksi', 'tipe')) {
            // LAKUKAN INI SEBELUM MENGUBAH TIPE KOLOM ENUM!

            // 1. Ubah kolom 'tipe' sementara menjadi VARCHAR agar bisa menerima nilai baru
            Schema::table('transaksi', function (Blueprint $table) {
                $table->string('tipe')->change();
            });

            // 2. Sekarang perbarui data di kolom 'tipe' agar sesuai dengan ENUM yang baru
            // Ubah 'pemasukan' menjadi 'penambahan'
            DB::table('transaksi')
                ->where('tipe', 'pemasukan')
                ->update(['tipe' => 'penambahan']);

            // Jika ada 'pengeluaran' yang perlu diubah menjadi 'pengurangan' (jika ENUM Anda sebelumnya 'pemasukan', 'pengeluaran')
            // Dan sekarang ENUM baru adalah 'penambahan', 'pengurangan'
            DB::table('transaksi')
                ->where('tipe', 'pengeluaran')
                ->update(['tipe' => 'pengurangan']); // Menyesuaikan jika ada perubahan dari 'pengeluaran' ke 'pengurangan'

            // 3. Ubah tipe kolom 'tipe' kembali menjadi ENUM yang benar
            Schema::table('transaksi', function (Blueprint $table) {
                $table->enum('tipe', ['penambahan', 'pengurangan'])->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Periksa apakah tabel 'transaksi' ada dan kolom 'tipe' ada
        if (Schema::hasTable('transaksi') && Schema::hasColumn('transaksi', 'tipe')) {
            // 1. Ubah kolom 'tipe' sementara menjadi VARCHAR sebelum membalikkan data
            Schema::table('transaksi', function (Blueprint $table) {
                $table->string('tipe')->change();
            });

            // 2. Balikkan nilai data yang dikonversi
            DB::table('transaksi')
                ->where('tipe', 'penambahan')
                ->update(['tipe' => 'pemasukan']);
            // Jika Anda mengubah 'pengeluaran' ke 'pengurangan' di up(), balikkan juga di sini
            DB::table('transaksi')
                ->where('tipe', 'pengurangan')
                ->update(['tipe' => 'pengeluaran']);

            // 3. Mengembalikan kolom ke tipe ENUM lama atau string umum
            Schema::table('transaksi', function (Blueprint $table) {
                // Asumsi Anda ingin mengembalikan ke ENUM lama ('pemasukan', 'pengeluaran')
                // Jika tidak, ganti dengan `$table->string('tipe')->change();`
                $table->enum('tipe', ['pemasukan', 'pengeluaran'])->change();
            });
        }
    }
};
