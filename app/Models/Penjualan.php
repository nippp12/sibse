<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany; // WAJIB Import MorphMany
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
// Import model-model yang diperlukan
use App\Models\Kas; // Digunakan untuk transaksi kas
use App\Models\KasTransaksi; // Digunakan untuk mencatat setiap transaksi kas
use App\Models\Sampah; // Digunakan untuk mengakses data master sampah
use App\Models\SampahTransaksi; // Digunakan untuk mencatat pergerakan stok sampah
use App\Models\User; // Digunakan untuk relasi petugas

class Penjualan extends Model
{
    use HasFactory;

    protected $table = 'penjualan';

    protected $fillable = [
        'petugas_id',
        'total_harga',
        'tanggal',
        'deskripsi',
        // 'kas_transaksi_id', // DIHAPUS dari fillable karena relasi sekarang polimorfik
        'status', // MENAMBAHKAN KEMBALI kolom status
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total_harga' => 'decimal:2',
        'status' => 'string', // Casting untuk status
    ];

    /*
    |--------------------------------------------------------------------------
    | Relasi (Relationships)
    |--------------------------------------------------------------------------
    */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function penjualanSampah(): HasMany
    {
        return $this->hasMany(PenjualanSampah::class, 'penjualan_id');
    }

    /**
     * Relasi polimorfik ke KasTransaksi.
     * Penjualan adalah sumber 'pemasukan' kas di KasTransaksi.
     * Menggunakan nama yang lebih spesifik untuk membedakan dengan 'Transaksi' saldo user.
     */
    public function kasTransaksiOrg(): MorphMany // DIKOREKSI: Menggunakan MorphMany
    {
        // 'transaksable' adalah nama relasi polimorfik yang digunakan di tabel kas_transaksi
        return $this->morphMany(KasTransaksi::class, 'transaksable');
    }

    /**
     * Relasi polimorfik ke SampahTransaksi.
     * Penjualan mengurangi stok sampah, jadi ia memiliki banyak SampahTransaksi.
     */
    public function sampahTransaksi(): MorphMany // DIKOREKSI: Menggunakan MorphMany
    {
        // 'transactable' adalah nama relasi polimorfik yang digunakan di tabel sampah_transaksi
        return $this->morphMany(SampahTransaksi::class, 'transactable');
    }

    /*
    |--------------------------------------------------------------------------
    | Boot Method & Event Listeners
    |--------------------------------------------------------------------------
    | Otomatis memperbarui saldo kas dan stok sampah saat Penjualan dibuat,
    | diperbarui, atau dihapus, dengan asumsi penjualan bersifat final saat dibuat
    | jika memiliki total_harga.
    */
    protected static function booted(): void
    {
        parent::boot();

        static::created(function (Penjualan $penjualan) {
            // Logika hanya akan dipicu jika status 'selesai' saat pembuatan
            if ($penjualan->status === 'selesai' && $penjualan->total_harga > 0) {
                DB::transaction(function () use ($penjualan) {
                    self::processPenjualanTransaction($penjualan);
                    self::createSampahTransactionsForCurrentItems($penjualan, 'pengurangan');
                });
            }
        });

        static::updated(function (Penjualan $penjualan) {
            $originalStatus = $penjualan->getOriginal('status');
            $newStatus = $penjualan->status;

            DB::transaction(function () use ($penjualan, $originalStatus, $newStatus) {
                // Logika saat status berubah menjadi 'selesai' dari status lain
                if ($newStatus === 'selesai' && $originalStatus !== 'selesai') {
                    if ($penjualan->total_harga > 0) {
                        Log::debug('Penjualan ID: ' . $penjualan->id . ' status berubah menjadi selesai. Memproses transaksi.');
                        self::processPenjualanTransaction($penjualan);
                        self::createSampahTransactionsForCurrentItems($penjualan, 'pengurangan');
                    }
                }
                // Logika saat status berubah dari 'selesai' (misal: kembali ke pending/dibatalkan)
                elseif ($originalStatus === 'selesai' && $newStatus !== 'selesai') {
                    Log::debug('Penjualan ID: ' . $penjualan->id . ' status berubah dari selesai ke ' . $newStatus . '. Membatalkan transaksi.');
                    self::cancelPenjualanTransaction($penjualan);
                    self::deleteAllSampahTransactionsForPenjualan($penjualan); // Hapus transaksi stok terkait
                }
                // Logika saat status tetap 'selesai' tapi total_harga berubah (rekonsiliasi)
                elseif ($newStatus === 'selesai' && $originalStatus === 'selesai' && $penjualan->isDirty('total_harga')) {
                    Log::debug('Penjualan ID: ' . $penjualan->id . ' status tetap selesai, total_harga berubah. Rekonsiliasi transaksi.');
                    self::cancelPenjualanTransaction($penjualan); // Batalkan transaksi lama
                    self::processPenjualanTransaction($penjualan); // Buat transaksi baru dengan jumlah baru
                    self::reconcileStockChanges($penjualan);      // Sesuaikan perubahan stok
                }
            });
        });

        // PENTING: Penanganan penghapusan cascading untuk relasi polimorfik
        static::deleting(function (Penjualan $penjualan) {
            // Batalkan transaksi hanya jika statusnya 'selesai' saat dihapus
            if ($penjualan->status === 'selesai') {
                Log::debug('Penjualan ID: ' . $penjualan->id . ' dengan status selesai sedang dihapus. Membatalkan transaksi terkait.');
                DB::transaction(function () use ($penjualan) { // Transaksi baru untuk memastikan atomicity penghapusan
                    self::cancelPenjualanTransaction($penjualan); // Batalkan transaksi kas

                    // Hapus semua SampahTransaksi yang terkait dengan penjualan ini
                    // Ini akan memicu event 'deleted' di SampahTransaksi untuk membalikkan stok
                    $penjualan->sampahTransaksi()->delete(); // Menggunakan relasi morphMany untuk menghapus
                    Log::debug('Semua SampahTransaksi terkait Penjualan ID: ' . $penjualan->id . ' dihapus.');
                });
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Metode Pembantu untuk Mengelola Transaksi & Stok
    |--------------------------------------------------------------------------
    */
    /**
     * Memproses transaksi kas untuk penjualan (pemasukan).
     * Akan membuat atau memperbarui record KasTransaksi.
     * @param Penjualan $penjualan
     */
    protected static function processPenjualanTransaction(Penjualan $penjualan): void
    {
        if ($penjualan->total_harga > 0) {
            // Coba temukan KasTransaksi yang sudah ada untuk penjualan ini berdasarkan relasi polimorfik
            $kasTransaksi = KasTransaksi::where('transaksable_type', Penjualan::class)
                                            ->where('transaksable_id', $penjualan->id)
                                            ->first();

            $kasPusat = Kas::first();
            if (!$kasPusat) {
                Log::error('Kas pusat tidak ditemukan untuk penjualan ID: ' . $penjualan->id);
                return;
            }

            if ($kasTransaksi) {
                // Jika sudah ada, perbarui jumlahnya
                $kasTransaksi->update([
                    'jumlah' => $penjualan->total_harga,
                ]);
                Log::debug('KasTransaksi ID: ' . $kasTransaksi->id . ' diperbarui untuk Penjualan ID: ' . $penjualan->id);
            } else {
                // Jika belum ada, buat yang baru
                $kasTransaksi = KasTransaksi::create([
                    'kas_id' => $kasPusat->id,
                    'jumlah' => $penjualan->total_harga,
                    'tipe' => 'pemasukan',
                    'deskripsi' => 'Pemasukan dari penjualan sampah ID: ' . $penjualan->id . ' (Petugas: ' . ($penjualan->petugas ? $penjualan->petugas->username : 'N/A') . ')',
                    'transaksable_type' => Penjualan::class,
                    'transaksable_id' => $penjualan->id,
                ]);
                // Tidak perlu memperbarui kas_transaksi_id di model Penjualan lagi karena sudah dihapus
                Log::debug('KasTransaksi baru ID: ' . $kasTransaksi->id . ' dibuat untuk Penjualan ID: ' . $penjualan->id);
            }
        }
    }

    /**
     * Membatalkan transaksi kas yang terkait dengan penjualan.
     * @param Penjualan $penjualan
     */
    protected static function cancelPenjualanTransaction(Penjualan $penjualan): void
    {
        // Coba temukan KasTransaksi berdasarkan relasi polimorfik
        $kasTransaksi = KasTransaksi::where('transaksable_type', Penjualan::class)
                                    ->where('transaksable_id', $penjualan->id)
                                    ->first();
        
        if ($kasTransaksi) {
            Log::debug('Membatalkan KasTransaksi ID: ' . $kasTransaksi->id . ' untuk Penjualan ID: ' . $penjualan->id);
            $kasTransaksi->delete(); // Ini akan memicu event 'deleted' di KasTransaksi
            Log::debug('KasTransaksi berhasil dihapus.');
        } else {
             Log::debug('Tidak ada KasTransaksi yang ditemukan untuk dibatalkan untuk Penjualan ID: ' . $penjualan->id);
        }
        // Tidak perlu menullkan 'kas_transaksi_id' di model Penjualan karena sudah dihapus
    }

    /**
     * Rekonsiliasi perubahan stok dan transaksi kas saat Penjualan diperbarui.
     * Akan menghapus SampahTransaksi lama dan membuat yang baru sesuai dengan item saat ini.
     * @param Penjualan $penjualan
     */
    protected static function reconcileStockChanges(Penjualan $penjualan): void
    {
        // Menggunakan deleteAllSampahTransactionsForPenjualan yang akan menggunakan relasi polimorfik
        self::deleteAllSampahTransactionsForPenjualan($penjualan);
        self::createSampahTransactionsForCurrentItems($penjualan, 'pengurangan');
        Log::debug('Rekonsiliasi stok untuk Penjualan ID: ' . $penjualan->id . ' selesai.');
    }

    /**
     * Membuat SampahTransaksi untuk setiap item dalam Penjualan.
     * @param Penjualan $penjualan
     * @param string $actionType Tipe aksi ('penambahan' atau 'pengurangan')
     */
    protected static function createSampahTransactionsForCurrentItems(Penjualan $penjualan, string $actionType): void
    {
        // Pastikan relasi 'penjualanSampah', 'sampah', dan 'satuan' dimuat
        $penjualan->loadMissing('penjualanSampah.sampah.satuan');

        foreach ($penjualan->penjualanSampah as $item) {
            if ($item->sampah) {
                $description = ($actionType === 'pengurangan' ? 'Pengurangan' : 'Penambahan') . ' stok dari penjualan ID: ' . $penjualan->id;

                SampahTransaksi::create([
                    'sampah_id' => $item->sampah->id,
                    'transactable_type' => Penjualan::class, // Menentukan tipe induk
                    'transactable_id' => $penjualan->id,     // Menentukan ID induk
                    'tipe' => $actionType,
                    'jumlah' => $item->qty,
                    'deskripsi' => $description . ' (Sampah: ' . $item->sampah->nama . ' - ' . ($item->sampah->satuan->nama ?? '') . ')',
                ]);
                Log::debug('SampahTransaksi baru dibuat untuk Penjualan ID: ' . $penjualan->id . ', Sampah ID: ' . $item->sampah->id . ', Jumlah: ' . $item->qty . ', Tipe: ' . $actionType);
            }
        }
    }

    /**
     * Menghapus semua SampahTransaksi yang terkait dengan Penjualan ini.
     * Ini akan memicu event 'deleted' di model SampahTransaksi untuk membalikkan stok.
     * @param Penjualan $penjualan
     */
    protected static function deleteAllSampahTransactionsForPenjualan(Penjualan $penjualan): void
    {
        Log::debug('deleteAllSampahTransactionsForPenjualan - Mencoba menghapus SampahTransaksi terkait secara eksplisit untuk Penjualan ID: ' . $penjualan->id);
        // Menggunakan relasi polimorfik untuk mengambil dan menghapus
        // Ini akan memicu event 'deleted' di SampahTransaksi
        $penjualan->sampahTransaksi()->delete();
        Log::debug('Penghapusan SampahTransaksi terkait selesai.');
    }
}
