<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log; // Penting untuk debugging

class Kas extends Model
{
    use HasFactory;

    protected $table = 'kas';

    protected $fillable = [
        'total_saldo',
        'last_updated',
    ];

    protected $casts = [
        'total_saldo' => 'decimal:2',
        'last_updated' => 'datetime',
    ];

    /**
     * Relasi One-to-Many dengan model KasTransaksi.
     * Setiap record Kas memiliki banyak transaksi kas.
     */
    public function kasTransaksi(): HasMany
    {
        return $this->hasMany(KasTransaksi::class, 'kas_id');
    }

    /**
     * Memperbarui total saldo kas secara penuh.
     * Metode ini akan menghitung ulang total saldo dari semua transaksi kas yang terkait.
     *
     * Metode ini seharusnya dipanggil dari event listener KasTransaksi (create, update, delete)
     * untuk memastikan saldo selalu akurat.
     */
    public function updateTotalSaldo(): void
    {
        // DIKOREKSI: Menggunakan nama kolom 'tipe' sesuai definisi ENUM di KasTransaksi
        $totalPemasukan = $this->kasTransaksi()
                               ->where('tipe', 'pemasukan') // Menggunakan 'tipe'
                               ->sum('jumlah');

        $totalPengeluaran = $this->kasTransaksi()
                                 ->where('tipe', 'pengeluaran') // Menggunakan 'tipe'
                                 ->sum('jumlah');

        $this->total_saldo = $totalPemasukan - $totalPengeluaran;
        $this->last_updated = now();
        $this->save(); // Simpan perubahan saldo
        
        Log::debug("Kas ID: {$this->id} total saldo diperbarui ke: {$this->total_saldo}");
    }
}
