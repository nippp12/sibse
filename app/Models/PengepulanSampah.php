<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengepulanSampah extends Model
{
    use HasFactory;

    protected $table = 'pengepulan_sampah';

    protected $fillable = [
        'pengepulan_id',
        'sampah_id',
        'qty',
    ];

    // Kolom 'timestamps' ada di migrasi Anda, jadi ini harus true atau dihapus
    public $timestamps = true; // Mengubah ini menjadi true

    protected $casts = [
        'qty' => 'decimal:2', // Casting untuk memastikan kuantitas ditangani sebagai desimal
    ];

    /**
     * Relasi Many-to-One dengan model Pengepulan.
     * Detail sampah ini milik satu sesi pengepulan.
     */
    public function pengepulan(): BelongsTo
    {
        return $this->belongsTo(Pengepulan::class, 'pengepulan_id'); // Eksplisitkan foreign key
    }

    /**
     * Relasi Many-to-One dengan model Sampah.
     * Detail sampah ini merujuk ke satu jenis sampah di master data.
     */
    public function sampah(): BelongsTo
    {
        return $this->belongsTo(Sampah::class, 'sampah_id'); // Eksplisitkan foreign key
    }
}