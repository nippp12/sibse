<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo; // Wajib diimpor untuk relasi polimorfik
use Illuminate\Support\Facades\DB; // Wajib diimpor untuk transaksi database
use Illuminate\Support\Facades\Log; // Wajib diimpor untuk logging

class KasTransaksi extends Model
{
    use HasFactory;

    protected $table = 'kas_transaksi';

    protected $fillable = [
        'kas_id',
        'jumlah',
        'tipe', // Diubah dari 'tipe_transaksi' sesuai database
        'deskripsi',
        'transaksable_id',   // Ditambahkan untuk kolom polimorfik
        'transaksable_type', // Ditambahkan untuk kolom polimorfik
        // 'tanggal' //dihapus karena tidak ada di database yang Anda berikan dan ditangani oleh timestamps
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'tipe' => 'string', // Diubah dari 'tipe_transaksi' sesuai database
        // 'tanggal' dihapus
    ];

    /**
     * Relasi Many-to-One dengan model Kas.
     */
    public function kas(): BelongsTo
    {
        return $this->belongsTo(Kas::class, 'kas_id'); // Menambahkan foreign key secara eksplisit
    }

    /**
     * Relasi polimorfik ke model pemilik transaksi (misal: Penjualan, PenarikanSaldo).
     */
    public function transaksable(): MorphTo // Menambahkan relasi polimorfik
    {
        return $this->morphTo();
    }

    protected static function booted(): void
    {
        parent::booted();

        // Event 'created': Saat record KasTransaksi baru dibuat
        static::created(function (KasTransaksi $kasTransaksi) {
            Log::debug("KasTransaksi CREATED (Event Fired). ID: {$kasTransaksi->id}, Kas ID: {$kasTransaksi->kas_id}, Jumlah: {$kasTransaksi->jumlah}, Tipe: {$kasTransaksi->tipe}, Sumber: {$kasTransaksi->transaksable_type} ({$kasTransaksi->transaksable_id})");
            DB::transaction(function () use ($kasTransaksi) {
                self::updateKasSaldo($kasTransaksi, 'add');
            });
        });

        // Event 'updated': Saat record KasTransaksi diubah
        static::updated(function (KasTransaksi $kasTransaksi) {
            // Hanya update saldo jika kolom 'jumlah', 'tipe', atau 'kas_id' berubah
            if ($kasTransaksi->isDirty('jumlah') || $kasTransaksi->isDirty('tipe') || $kasTransaksi->isDirty('kas_id')) {
                $originalAmount = $kasTransaksi->getOriginal('jumlah');
                $originalType = $kasTransaksi->getOriginal('tipe');
                $originalKasId = $kasTransaksi->getOriginal('kas_id');

                Log::debug("KasTransaksi UPDATED (Event Fired). ID: {$kasTransaksi->id}, Old Jumlah: {$originalAmount}, Old Tipe: {$originalType}, Old Kas ID: {$originalKasId}, New Jumlah: {$kasTransaksi->jumlah}, New Tipe: {$kasTransaksi->tipe}, New Kas ID: {$kasTransaksi->kas_id}");

                DB::transaction(function () use ($kasTransaksi, $originalAmount, $originalType, $originalKasId) {
                    // Batalkan efek transaksi lama pada saldo Kas asli
                    Log::debug("UPDATED (Transaction): Reverting old Kas saldo for KasTransaksi ID: {$kasTransaksi->id}. Original Jumlah: {$originalAmount}, Original Tipe: {$originalType}");
                    $kasToRevert = Kas::find($originalKasId);
                    if ($kasToRevert) {
                         // Lakukan operasi kebalikan dari tipe lama
                         if ($originalType === 'pemasukan') {
                            $kasToRevert->total_saldo -= $originalAmount;
                        } else { // pengeluaran
                            $kasToRevert->total_saldo += $originalAmount;
                        }
                        $kasToRevert->last_updated = now();
                        $kasToRevert->saveQuietly(); // Gunakan saveQuietly
                        Log::debug("Reverted Kas ID {$originalKasId} balance to: {$kasToRevert->total_saldo}");
                    } else {
                        Log::error("Original Kas ID {$originalKasId} not found for reversion.");
                    }

                    // Terapkan efek transaksi baru pada saldo Kas saat ini
                    Log::debug("UPDATED (Transaction): Applying new Kas saldo for KasTransaksi ID: {$kasTransaksi->id}. New Jumlah: {$kasTransaksi->jumlah}, New Tipe: {$kasTransaksi->tipe}");
                    self::updateKasSaldo($kasTransaksi, 'add');
                });
            }
        });

        // Event 'deleted': Saat record KasTransaksi dihapus
        static::deleted(function (KasTransaksi $kasTransaksi) {
            Log::debug("KasTransaksi DELETED (Event Fired). ID: {$kasTransaksi->id}, Kas ID: {$kasTransaksi->kas_id}, Original Jumlah: {$kasTransaksi->jumlah}, Original Tipe: {$kasTransaksi->tipe}");
            DB::transaction(function () use ($kasTransaksi) {
                // Balikkan efek transaksi pada saldo Kas saat transaksi dihapus
                self::updateKasSaldo($kasTransaksi, 'remove');
            });
        });
    }

    /**
     * Metode pembantu untuk memperbarui saldo Kas pusat.
     * Mengakses record Kas terkait dan memperbarui 'total_saldo' serta 'last_updated'.
     */
    protected static function updateKasSaldo(self $kasTransaksi, string $action): void
    {
        $kas = Kas::find($kasTransaksi->kas_id); // Temukan Kas berdasarkan kas_id dalam transaksi ini
        if (!$kas) {
            Log::error('Record Kas (ID: ' . $kasTransaksi->kas_id . ') tidak ditemukan. Tidak dapat memperbarui saldo.');
            return;
        }

        $jumlah = $kasTransaksi->jumlah;
        $tipe = $kasTransaksi->tipe;

        switch ($action) {
            case 'add':
                if ($tipe === 'pemasukan') {
                    $kas->total_saldo += $jumlah;
                } else { // pengeluaran
                    $kas->total_saldo -= $jumlah;
                }
                Log::debug("Kas diperbarui (ADD): ID {$kas->id}, Ditambah/dikurang {$jumlah} ({$tipe}). Saldo baru: {$kas->total_saldo}");
                break;
            case 'remove':
                // Membalikkan efek saat transaksi dihapus
                if ($tipe === 'pemasukan') {
                    $kas->total_saldo -= $jumlah;
                } else { // pengeluaran
                    $kas->total_saldo += $jumlah;
                }
                Log::debug("Kas diperbarui (REMOVE): ID {$kas->id}, Dihapus {$jumlah} ({$tipe}). Saldo baru: {$kas->total_saldo}");
                break;
        }
        $kas->last_updated = now(); // Perbarui waktu terakhir update
        $kas->saveQuietly(); // Gunakan saveQuietly untuk mencegah loop event
        Log::debug("Kas ID {$kas->id} saved successfully with new saldo: {$kas->total_saldo}");
    }
}
