<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_users', function (Blueprint $table) {
            $table->id();

            $table->foreignId('broadcast_id')->constrained('broadcast')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->enum('status_kirim', ['pending', 'sukses', 'gagal'])->default('pending');
            $table->timestamp('waktu_kirim')->nullable();

            $table->text('deskripsi')->nullable();   // hasil response dari API
            $table->string('message_id')->nullable(); // opsional, dari Fonnte

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_users');
    }
};
