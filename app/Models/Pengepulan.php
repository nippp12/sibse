<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany; // WAJIB Import MorphMany
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
// Import model yang dibutuhkan
use App\Models\Sampah;
use App\Models\SampahTransaksi;
use App\Models\Transaksi; // Mengacu pada model Transaksi (saldo user) Anda
use App\Models\User;
// use App\Models\Kas; // Tidak digunakan langsung di sini

class Pengepulan extends Model
{
    use HasFactory;

    protected $table = 'pengepulan';

    protected $fillable = [
        'user_id',
        'petugas_id',
        'broadcast_id',
        'metode_pengambilan',
        'lokasi',
        'status',
        'total_harga',
        'tanggal',
        // 'transaksi_id', // DIHAPUS: Karena transaksi saldo user sekarang polimorfik
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total_harga' => 'decimal:2',
        'status' => 'string',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relasi (Relationships)
    |--------------------------------------------------------------------------
    */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(Broadcast::class, 'broadcast_id');
    }

    /**
     * Relasi HasMany ke PengepulanSampah.
     */
    public function pengepulanSampah(): HasMany
    {
        return $this->hasMany(PengepulanSampah::class, 'pengepulan_id');
    }

    /**
     * Relasi polimorfik ke Transaksi (saldo user).
     * Pengepulan menghasilkan penambahan saldo untuk user.
     */
    public function transaksiUser(): MorphMany // Mengubah nama relasi agar lebih jelas
    {
        // 'transactable' adalah nama relasi polimorfik yang kita gunakan
        // di tabel transaksi (transactable_id, transactable_type)
        return $this->morphMany(Transaksi::class, 'transactable');
    }

    /**
     * Relasi polimorfik ke SampahTransaksi.
     * Pengepulan memiliki banyak SampahTransaksi (pergerakan stok).
     */
    public function sampahTransaksi(): MorphMany // TETAP POLIMORFIK SESUAI PERMINTAAN USER
    {
        return $this->morphMany(SampahTransaksi::class, 'transactable');
    }

    /*
    |--------------------------------------------------------------------------
    | Boot Method & Event Listeners
    |--------------------------------------------------------------------------
    */
    protected static function booted(): void
    {
        parent::boot();

        static::created(function (Pengepulan $pengepulan) {
            if ($pengepulan->status === 'selesai') {
                DB::transaction(function () use ($pengepulan) {
                    self::processPengepulanTransaction($pengepulan);
                    self::createSampahTransactionsForCurrentItems($pengepulan, 'penambahan');
                });
            }
        });

        static::updated(function (Pengepulan $pengepulan) {
            $originalStatus = $pengepulan->getOriginal('status');
            $newStatus = $pengepulan->status;

            DB::transaction(function () use ($pengepulan, $originalStatus, $newStatus) {
                if ($pengepulan->isDirty('status') || $pengepulan->isDirty('total_harga')) {
                    if ($newStatus === 'selesai' && $originalStatus !== 'selesai') {
                        self::processPengepulanTransaction($pengepulan);
                        self::createSampahTransactionsForCurrentItems($pengepulan, 'penambahan');
                    } elseif ($originalStatus === 'selesai' && $newStatus !== 'selesai') {
                        self::cancelPengepulanTransaction($pengepulan);
                        self::deleteAllSampahTransactionsForPengepulan($pengepulan);
                    } elseif ($newStatus === 'selesai' && $originalStatus === 'selesai' && $pengepulan->isDirty('total_harga')) {
                        self::cancelPengepulanTransaction($pengepulan);
                        self::processPengepulanTransaction($pengepulan);
                        self::reconcileStockChanges($pengepulan);
                    }
                }
            });
        });

        static::deleting(function (Pengepulan $pengepulan) {
            $originalStatus = $pengepulan->getOriginal('status');

            Log::debug('Pengepulan::deleting - Memulai proses penghapusan untuk Pengepulan ID: ' . $pengepulan->id);
            
            if ($originalStatus === 'selesai') {
                // Batalkan transaksi saldo user yang terkait secara eksplisit
                Log::debug('Pengepulan::deleting - Mencoba menghapus Transaksi (saldo user) terkait secara eksplisit.');
                $pengepulan->transaksiUser()->get()->each(function (Transaksi $tu) { // Menggunakan relasi transaksiUser
                    Log::debug('Pengepulan::deleting - Memanggil delete() pada Transaksi (saldo user) ID: ' . $tu->id);
                    $tu->delete(); // Ini akan memicu Transaksi::deleted event
                });
                Log::debug('Pengepulan::deleting - Penghapusan Transaksi (saldo user) terkait selesai.');
            }
            
            // Hapus semua SampahTransaksi yang terkait dengan pengepulan ini secara eksplisit.
            // Ini akan lebih memastikan bahwa event 'deleted' di model SampahTransaksi terpicu untuk setiap record.
            Log::debug('Pengepulan::deleting - Mencoba menghapus SampahTransaksi terkait secara eksplisit.');
            $pengepulan->sampahTransaksi()->get()->each(function (SampahTransaksi $st) {
                Log::debug('Pengepulan::deleting - Memanggil delete() pada SampahTransaksi ID: ' . $st->id);
                $st->delete(); // Ini seharusnya memicu SampahTransaksi::deleted event
            });
            Log::debug('Pengepulan::deleting - Penghapusan SampahTransaksi terkait selesai.');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Metode Pembantu untuk Mengelola Transaksi & Stok (Pengepulan)
    |--------------------------------------------------------------------------
    */
    protected static function processPengepulanTransaction(Pengepulan $pengepulan): void
    {
        // PENTING: Sekarang membuat Transaksi (saldo user) secara polimorfik
        if ($pengepulan->total_harga > 0) { // Tidak perlu cek transaksi_id null lagi
            // Coba temukan Transaksi (saldo user) yang sudah ada untuk pengepulan ini
            $transaksiUser = Transaksi::where('transactable_type', Pengepulan::class)
                                       ->where('transactable_id', $pengepulan->id)
                                       ->first();

            if ($transaksiUser) {
                // Jika sudah ada, perbarui jumlahnya
                $transaksiUser->update([
                    'jumlah' => $pengepulan->total_harga,
                    'user_id' => $pengepulan->user_id, // Pastikan user_id juga terupdate jika berubah
                ]);
                Log::debug('Transaksi (saldo user) ID: ' . $transaksiUser->id . ' diperbarui untuk Pengepulan ID: ' . $pengepulan->id);
            } else {
                // Jika belum ada, buat yang baru
                $transaksiUser = Transaksi::create([
                    'user_id' => $pengepulan->user_id,
                    'jumlah' => $pengepulan->total_harga,
                    'tipe' => 'penambahan', // Pengepulan adalah penambahan saldo untuk user
                    'deskripsi' => 'Penambahan saldo dari pengepulan sampah ID: ' . $pengepulan->id . ' (User: ' . ($pengepulan->user ? $pengepulan->user->username : 'N/A') . ')',
                    'transactable_type' => Pengepulan::class, // Menentukan tipe induk
                    'transactable_id' => $pengepulan->id,     // Menentukan ID induk
                ]);
                Log::debug('Transaksi (saldo user) baru ID: ' . $transaksiUser->id . ' dibuat untuk Pengepulan ID: ' . $pengepulan->id);
            }
        }
    }

    protected static function cancelPengepulanTransaction(Pengepulan $pengepulan): void
    {
        // PENTING: Sekarang menghapus Transaksi (saldo user) secara polimorfik
        Log::debug('Membatalkan Transaksi (saldo user) untuk Pengepulan ID: ' . $pengepulan->id);
        $pengepulan->transaksiUser()->get()->each(function (Transaksi $tu) { // Menggunakan relasi transaksiUser
            Log::debug('Memanggil delete() pada Transaksi (saldo user) ID: ' . $tu->id);
            $tu->delete(); // Ini akan memicu event 'deleted' di Transaksi (saldo user)
        });
        Log::debug('Semua Transaksi (saldo user) terkait Pengepulan ID: ' . $pengepulan->id . ' dihapus.');

        // Tidak perlu menullkan 'transaksi_id' di model Pengepulan karena sudah tidak ada
    }

    protected static function reconcileStockChanges(Pengepulan $pengepulan): void
    {
        // Hapus semua SampahTransaksi yang terkait dengan pengepulan ini secara eksplisit.
        self::deleteAllSampahTransactionsForPengepulan($pengepulan);
        // Buat ulang SampahTransaksi untuk item-item saat ini
        self::createSampahTransactionsForCurrentItems($pengepulan, 'penambahan');
    }

    protected static function createSampahTransactionsForCurrentItems(Pengepulan $pengepulan, string $actionType): void
    {
        $pengepulan->loadMissing('pengepulanSampah.sampah.satuan');

        foreach ($pengepulan->pengepulanSampah as $item) {
            if ($item->sampah) {
                $description = ($actionType === 'penambahan' ? 'Penambahan' : 'Pengurangan') . ' stok dari pengepulan ID: ' . $pengepulan->id;

                SampahTransaksi::create([
                    'sampah_id' => $item->sampah->id,
                    'transactable_type' => Pengepulan::class, // Menggunakan transactable_type
                    'transactable_id' => $pengepulan->id,     // Menggunakan transactable_id
                    'tipe' => $actionType,
                    'jumlah' => $item->qty,
                    'deskripsi' => $description . ' (Sampah: ' . $item->sampah->nama . ' - ' . ($item->sampah->satuan->nama ?? '') . ')',
                ]);
            }
        }
    }

    protected static function deleteAllSampahTransactionsForPengepulan(Pengepulan $pengepulan): void
    {
        Log::debug('deleteAllSampahTransactionsForPengepulan - Mencoba menghapus SampahTransaksi terkait secara eksplisit.');
        $pengepulan->sampahTransaksi()->get()->each(function (SampahTransaksi $st) {
            Log::debug('deleteAllSampahTransactionsForPengepulan - Memanggil delete() pada SampahTransaksi ID: ' . $st->id);
            $st->delete(); // Ini seharusnya memicu SampahTransaksi::deleted event
        });
        Log::debug('deleteAllSampahTransactionsForPengepulan - Penghapusan SampahTransaksi terkait selesai.');
    }
}
