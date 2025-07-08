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
        Schema::create('kas_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kas_id')->constrained('kas')->onDelete('cascade');
            $table->decimal('jumlah', 12, 2);
            $table->string('tipe_transaksi')->default('pemasukan');
            $table->string('deskripsi')->nullable();
            $table->timestamp('tanggal')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kas_transaksi');
    }
};
