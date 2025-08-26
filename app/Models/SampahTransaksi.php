<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SampahTransaksi extends Model
{
    use HasFactory;

    protected $table = 'sampah_transaksi';

    protected $fillable = [
        'sampah_id',
        'tipe',
        'jumlah',
        'deskripsi',
        'transactable_id',
        'transactable_type',
        'harga',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'tipe'   => 'string',
        'harga'  => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relasi (Relationships)
    |--------------------------------------------------------------------------
    */
    public function sampah(): BelongsTo
    {
        return $this->belongsTo(Sampah::class, 'sampah_id');
    }

    public function transactable(): MorphTo
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | Boot Method & Event Listeners
    |--------------------------------------------------------------------------
    */
    protected static function booted(): void
    {
        parent::boot();

        static::created(function (SampahTransaksi $sampahTransaksi) {
            Log::debug("SampahTransaksi CREATED (Event Fired). ID: {$sampahTransaksi->id}, Sampah ID: {$sampahTransaksi->sampah_id}, Jumlah: {$sampahTransaksi->jumlah}, Tipe: {$sampahTransaksi->tipe}");
            self::applyStockChange($sampahTransaksi, $sampahTransaksi->jumlah);
            // Pastikan harga tersimpan saat create
            if (is_null($sampahTransaksi->harga)) {
                $sampahTransaksi->harga = $sampahTransaksi->sampah->harga ?? 0;
                $sampahTransaksi->saveQuietly();
            }
        });

        static::updated(function (SampahTransaksi $sampahTransaksi) {
            if ($sampahTransaksi->isDirty('jumlah') || $sampahTransaksi->isDirty('tipe') || $sampahTransaksi->isDirty('sampah_id')) {
                $originalSampahId = $sampahTransaksi->getOriginal('sampah_id');
                $originalJumlah = $sampahTransaksi->getOriginal('jumlah');
                $originalTipe   = $sampahTransaksi->getOriginal('tipe');

                Log::debug("SampahTransaksi UPDATED (Event Fired). ID: {$sampahTransaksi->id}, Old Jumlah: {$originalJumlah}, Old Tipe: {$originalTipe}, New Jumlah: {$sampahTransaksi->jumlah}, New Tipe: {$sampahTransaksi->tipe}");

                DB::transaction(function () use ($sampahTransaksi, $originalSampahId, $originalJumlah, $originalTipe) {
                    Log::debug("UPDATED (Transaction): Reverting old stock change for ST ID: {$sampahTransaksi->id}. Original Jumlah: {$originalJumlah}, Original Tipe: {$originalTipe}");
                    self::applyStockChange($sampahTransaksi, $originalJumlah, ($originalTipe === 'penambahan' ? 'pengurangan' : 'penambahan'), $originalSampahId);
                    
                    Log::debug("UPDATED (Transaction): Applying new stock change for ST ID: {$sampahTransaksi->id}. New Jumlah: {$sampahTransaksi->jumlah}, New Tipe: {$sampahTransaksi->tipe}");
                    self::applyStockChange($sampahTransaksi, $sampahTransaksi->jumlah);
                });
            }
            // Pastikan harga tersimpan saat update jika belum ada
            if (is_null($sampahTransaksi->harga)) {
                $sampahTransaksi->harga = $sampahTransaksi->sampah->harga ?? 0;
                $sampahTransaksi->saveQuietly();
            }
        });

        static::deleted(function (SampahTransaksi $sampahTransaksi) {
            // DIKOREKSI: Membungkus dalam transaksi jika diperlukan, tapi hati-hati dengan nesting
            // Jika delete ini dipicu dari DB::transaction di Pengepulan, transaksi ini akan nested.
            // Namun, untuk reversal stok, seringkali tetap aman jika transaksi utama tidak rollback.
            DB::transaction(function () use ($sampahTransaksi) {
                $reverseTipe = $sampahTransaksi->tipe === 'penambahan' ? 'pengurangan' : 'penambahan';

                Log::debug("SampahTransaksi DELETED (Event Fired). ID: {$sampahTransaksi->id}, Sampah ID: {$sampahTransaksi->sampah_id}, Original Jumlah: {$sampahTransaksi->jumlah}, Original Tipe: {$sampahTransaksi->tipe}, Reverse Tipe: {$reverseTipe}");

                // Apply stock change and save the Sampah model to persist stock update
                self::applyStockChange($sampahTransaksi, $sampahTransaksi->jumlah, $reverseTipe);
                $sampah = Sampah::find($sampahTransaksi->sampah_id);
                if ($sampah) {
                    $sampah->saveQuietly();
                }
            });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Metode Pembantu untuk Mengelola Perubahan Stok
    |--------------------------------------------------------------------------
    */
    protected static function applyStockChange(
        self $sampahTransaksi,
        float $jumlah,
        ?string $tipe = null,
        ?int $sampahId = null
    ): void {
        $sampah = Sampah::find($sampahId ?? $sampahTransaksi->sampah_id);
        $tipe = $tipe ?? $sampahTransaksi->tipe;
        
        Log::debug("applyStockChange called. Args: Sampah ID: " . ($sampahId ?? $sampahTransaksi->sampah_id) . ", Jumlah: {$jumlah}, Tipe: {$tipe}");

        if ($sampah) {
            $currentStock = $sampah->stock;
            $newStock = $currentStock;

            if ($tipe === 'penambahan') {
                $newStock += $jumlah;
            } elseif ($tipe === 'pengurangan') {
                $newStock -= $jumlah;
            }

            if ($newStock < 0) {
                Log::warning("Stok negatif dicegah untuk Sampah ID: {$sampah->id}. Mengatur stok ke 0.");
                $newStock = 0;
            }

            Log::debug("applyStockChange => Sampah ID: {$sampah->id}, Old Stock: {$currentStock}, New Stock: {$newStock}, Final Jumlah: {$jumlah}, Final Tipe: {$tipe}");

            $sampah->stock = $newStock;
            $isSaved = $sampah->saveQuietly(); // DIKOREKSI: Menggunakan saveQuietly()
            Log::debug("Sampah::saveQuietly() result for Sampah ID {$sampah->id}: " . ($isSaved ? 'TRUE' : 'FALSE'));
        } else {
            Log::error("Sampah with ID: " . ($sampahId ?? $sampahTransaksi->sampah_id) . " not found in applyStockChange. Stock not updated.");
        }
    }
}
