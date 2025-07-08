<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast', function (Blueprint $table) {
            $table->id();

            $table->string('judul');
            $table->text('pesan');

            // 👇 Field baru yang disepakati
            $table->enum('jenis', ['informasi', 'pengepulan', 'penarikan'])->default('informasi');
            $table->timestamp('jadwal_kirim')->nullable(); // waktu kirim WA
            $table->date('tanggal_acara')->nullable();     // tanggal event / pengepulan
            $table->string('lokasi')->nullable();           // lokasi acara

            $table->boolean('mention_user')->default(false); // apakah pakai @mention
            $table->timestamp('terkirim')->nullable();       // waktu semua pesan terkirim

            $table->foreignId('dibuat_oleh')->constrained('users')->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast');
    }
};
