<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengepulan_sampah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengepulan_id')->constrained('pengepulan')->onDelete('cascade');
            $table->foreignId('sampah_id')->constrained('sampah')->onDelete('cascade');
            $table->decimal('qty', 12, 2)->nullable(false);
            $table->timestamps(); // Menambahkan created_at dan updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengepulan_sampah');
    }
};
