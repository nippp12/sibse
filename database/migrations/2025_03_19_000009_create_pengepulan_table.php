<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengepulan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('petugas_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('broadcast_id')->nullable()->constrained('broadcast')->onDelete('cascade');

            $table->string('metode_pengambilan');
            $table->string('lokasi')->nullable();

            $table->enum('status', ['pending', 'diproses', 'selesai', 'dibatalkan'])->default('pending');
            $table->decimal('total_harga', 12, 2)->default(0);

            $table->foreignId('transaksi_id')->nullable()
                  ->constrained('transaksi')
                  ->onDelete('set null');

            $table->date('tanggal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengepulan');
    }
};
