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
        // === Definisi Tabel 'users' ===
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('alamat')->nullable(); // Dibuat nullable karena umumnya tidak selalu wajib diisi
            $table->string('no_hp')->unique()->nullable(); // Dibuat nullable karena umumnya tidak selalu wajib diisi

            // Menambahkan kolom saldo ke tabel 'users'
            // Menggunakan decimal(12, 2) untuk akurasi uang, dengan default 0
            $table->decimal('saldo', 12, 2)->default(0);

            // Menambahkan kolom 'role_id' sebagai foreign key ke tabel 'roles' (dari Spatie/laravel-permission).
            // PENTING: Migrasi ini HARUS dijalankan SETELAH migrasi 'create_permission_tables'
            // dari paket Spatie/laravel-permission agar tabel 'roles' sudah ada.
            // Kolom ini dibuat tidak nullable karena Forms\Components\Select::make('role_id')->required()
            // di UserResource yang mungkin Anda gunakan. Pastikan Anda selalu menetapkan role saat membuat user.
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade'); // onDelete('cascade') akan menghapus user jika role-nya dihapus

            $table->rememberToken();
            $table->timestamps(); // Menambahkan created_at dan updated_at
        });

        // === Definisi Tabel 'password_reset_tokens' ===
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // === Definisi Tabel 'sessions' ===
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Tabel 'saldo' telah dihapus dari sini karena kolom 'saldo' sudah terintegrasi langsung di tabel 'users'.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Urutan drop tabel penting jika ada foreign key.
        // Hapus tabel yang punya foreign key duluan, baru tabel yang dirujuk.
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users'); // Drop tabel users terakhir
    }
};
