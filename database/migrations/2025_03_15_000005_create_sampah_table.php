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
    Schema::create('sampah', function (Blueprint $table) {
        $table->id('id'); // Primary Key
        $table->string('nama')->notNullable();
        $table->string('image')->nullable();
        $table->foreignId('jenis_sampah_id')->constrained('jenis_sampah', 'id')->onDelete('cascade');
        $table->foreignId('satuan_id')->constrained('satuan', 'id')->onDelete('cascade');
        $table->decimal('stock', 12, 2)->default(0);
        $table->decimal('harga', 12, 2)->notNullable();
        $table->string('deskripsi')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sampah');
    }
};
