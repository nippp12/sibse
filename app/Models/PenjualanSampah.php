<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenjualanSampah extends Model
{
    use HasFactory;

    // Menentukan nama tabel secara eksplisit
    protected $table = 'penjualan_sampah';

    // Mendefinisikan atribut yang dapat diisi secara massal
    protected $fillable = [
        'penjualan_id', // Foreign key ke tabel penjualan
        'sampah_id',    // Foreign key ke tabel sampah
        'qty',          // Kuantitas sampah yang dijual
        'harga_per_unit', // Ditambahkan: Harga per unit saat penjualan terjadi
    ];

    // Secara default, Eloquent mengasumsikan kolom 'created_at' dan 'updated_at'.
    // Karena migrasi Anda menyertakan timestamps, ini harus 'true' (atau bisa dihilangkan karena defaultnya sudah true).
    public $timestamps = true;

    // Casting atribut ke tipe data asli yang sesuai
    protected $casts = [
        'qty' => 'decimal:2', // Memastikan kuantitas ditangani sebagai desimal dengan 2 angka di belakang koma
        'harga_per_unit' => 'decimal:2', // Ditambahkan: Memastikan harga_per_unit ditangani sebagai desimal
    ];

    /*
    |--------------------------------------------------------------------------
    | Relasi (Relationships)
    |--------------------------------------------------------------------------
    */

    /**
     * Relasi Many-to-One dengan model Penjualan.
     * Detail sampah ini milik satu sesi penjualan.
     */
    public function penjualan(): BelongsTo
    {
        // Mendefinisikan relasi 'belongsTo' ke model Penjualan, menggunakan foreign key 'penjualan_id'
        return $this->belongsTo(Penjualan::class, 'penjualan_id');
    }

    /**
     * Relasi Many-to-One dengan model Sampah.
     * Detail sampah ini merujuk ke satu jenis sampah di master data.
     */
    public function sampah(): BelongsTo
    {
        // Mendefinisikan relasi 'belongsTo' ke model Sampah, menggunakan foreign key 'sampah_id'
        return $this->belongsTo(Sampah::class, 'sampah_id');
    }
}

