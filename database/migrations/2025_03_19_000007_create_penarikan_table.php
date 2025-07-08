<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::create('penarikan', function (Blueprint $table) {
            $table->id();
            // Kunci asing ke tabel users. Satu pengguna bisa memiliki banyak pengajuan penarikan.
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // Jumlah nominal yang diajukan untuk ditarik.
            $table->decimal('jumlah', 12, 2)->comment('Jumlah nominal yang diajukan untuk ditarik.');
            // Status pengajuan: "pending" (menunggu persetujuan), "approved" (disetujui), "rejected" (ditolak).
            $table->string('status')->default('pending')->comment('Status pengajuan: "pending", "approved", "rejected".');
            // Tanggal dan waktu pengajuan penarikan. Akan otomatis diisi saat dibuat.
            $table->timestamp('tanggal_pengajuan')->useCurrent()->comment('Tanggal pengajuan penarikan.');
            $table->timestamps(); // Menambahkan kolom created_at dan updated_at
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('penarikan');
    }
};
