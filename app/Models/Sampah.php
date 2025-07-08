<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // WAJIB ada
use Illuminate\Database\Eloquent\Relations\HasMany; // WAJIB ada

class Sampah extends Model
{
    use HasFactory;

    // Nama tabel secara eksplisit, sesuai migrasi Anda
    protected $table = 'sampah';

    // Kolom yang aman untuk mass assignment
    protected $fillable = [
        'nama',
        'image',
        'jenis_sampah_id',
        'satuan_id',
        'stock', // Ditambahkan kembali karena ada di migrasi
        'harga',
        'deskripsi',
    ];

    // Casting untuk tipe data agar sesuai dengan migrasi (decimal)
    protected $casts = [
        'stock' => 'decimal:2', // Pastikan stock di-cast sebagai decimal
        'harga' => 'decimal:2', // Pastikan harga di-cast sebagai decimal
    ];

    /**
     * Relasi dengan model JenisSampah.
     * Satu jenis sampah spesifik (misal: "Botol PET") termasuk dalam satu kategori jenis sampah (misal: "Plastik").
     */
    public function jenis() // Mengubah nama method dari 'jenis' menjadi 'jenisSampah' agar lebih eksplisit
    {
        return $this->belongsTo(JenisSampah::class, 'jenis_sampah_id');
    }

    /**
     * Relasi dengan model Satuan.
     * Setiap item sampah memiliki satu satuan pengukuran (misal: Kg, pcs).
     */
    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    /**
     * Relasi dengan model PengepulanSampah.
     * Satu jenis sampah bisa muncul dalam banyak detail pengepulan.
     */
    public function pengepulanSampah()
    {
        return $this->hasMany(PengepulanSampah::class, 'sampah_id');
    }

    /**
     * Relasi dengan model SampahTransaksi.
     * Satu jenis sampah bisa memiliki banyak riwayat perubahan stok.
     */
    public function sampahTransaksi()
    {
        return $this->hasMany(SampahTransaksi::class, 'sampah_id');
    }
}