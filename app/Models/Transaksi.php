<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo; // Wajib diimpor untuk relasi polimorfik
use Illuminate\Support\Facades\DB; // Diperlukan untuk transaksi database
use Illuminate\Support\Facades\Log; // Diperlukan untuk logging

class Transaksi extends Model // Ini adalah model untuk transaksi saldo user
{
    use HasFactory;

    protected $table = 'transaksi'; // Tabel yang digunakan untuk menyimpan transaksi saldo user

    protected $fillable = [
        'user_id',
        'jumlah',
        'tipe', // 'penambahan' atau 'pengurangan' saldo user
        'deskripsi',
        'transactable_type', // Kolom polimorfik: Tipe model pemilik (e.g., App\Models\Pengepulan)
        'transactable_id',   // Kolom polimorfik: ID model pemilik (e.g., ID dari Pengepulan)
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
        'tipe' => 'string',
    ];

    /**
     * Relasi Many-to-One dengan model User.
     * Setiap transaksi saldo terkait dengan satu pengguna.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi polimorfik ke model pemilik transaksi saldo (misal: Pengepulan, PenarikanSaldo).
     */
    public function transactable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Booted method untuk mendefinisikan event listeners.
     * Ini akan secara otomatis memperbarui saldo pengguna saat transaksi dibuat, diupdate, atau dihapus.
     */
    protected static function booted(): void // Menggunakan booted() bukan boot()
    {
        parent::boot();

        // Ketika transaksi BARU dibuat
        static::created(function (Transaksi $transaksi) {
            Log::debug("User Transaksi CREATED. ID: {$transaksi->id}, User ID: {$transaksi->user_id}, Jumlah: {$transaksi->jumlah}, Tipe: {$transaksi->tipe}, Sumber: {$transaksi->transactable_type} ({$transaksi->transactable_id})");
            DB::transaction(function () use ($transaksi) {
                self::applyBalanceChange($transaksi, $transaksi->jumlah, $transaksi->tipe); // Tambahkan tipe
            });
        });

        // Ketika transaksi DIUPDATE
        static::updated(function (Transaksi $transaksi) {
            // Periksa apakah kolom 'jumlah', 'tipe', atau 'user_id' berubah
            if ($transaksi->isDirty('jumlah') || $transaksi->isDirty('tipe') || $transaksi->isDirty('user_id')) {
                $originalUserId = $transaksi->getOriginal('user_id');
                $originalJumlah = $transaksi->getOriginal('jumlah');
                $originalTipe = $transaksi->getOriginal('tipe');

                Log::debug("User Transaksi UPDATED. ID: {$transaksi->id}, Old Jumlah: {$originalJumlah}, Old Tipe: {$originalTipe}, New Jumlah: {$transaksi->jumlah}, New Tipe: {$transaksi->tipe}");

                DB::transaction(function () use ($transaksi, $originalUserId, $originalJumlah, $originalTipe) {
                    // 1. Batalkan perubahan saldo dari nilai asli pada user lama (jika user_id berubah) atau user yang sama
                    Log::debug("UPDATED (Transaction): Reverting old balance for User ID: {$originalUserId}. Original Jumlah: {$originalJumlah}, Original Tipe: {$originalTipe}");
                    $userToRevert = User::find($originalUserId);
                    if ($userToRevert) {
                         // Balikkan tipe untuk mengembalikan saldo
                         $reverseOriginalTipe = ($originalTipe === 'penambahan') ? 'pengurangan' : 'penambahan';
                         self::applyBalanceChange($transaksi, $originalJumlah, $reverseOriginalTipe, $originalUserId);
                         Log::debug("Reverted User ID {$originalUserId} balance to: {$userToRevert->saldo}");
                    } else {
                        Log::error("Original User ID {$originalUserId} not found for balance reversion.");
                    }
                    
                    // 2. Terapkan perubahan saldo dengan nilai yang baru pada user yang baru/sama
                    Log::debug("UPDATED (Transaction): Applying new balance for User ID: {$transaksi->user_id}. New Jumlah: {$transaksi->jumlah}, New Tipe: {$transaksi->tipe}");
                    self::applyBalanceChange($transaksi, $transaksi->jumlah, $transaksi->tipe);
                });
            }
        });

        // Ketika transaksi DIHAPUS
        static::deleted(function (Transaksi $transaksi) {
            Log::debug("User Transaksi DELETED. ID: {$transaksi->id}, User ID: {$transaksi->user_id}, Jumlah: {$transaksi->jumlah}, Tipe: {$transaksi->tipe}");
            DB::transaction(function () use ($transaksi) {
                // Balikkan efek transaksi yang dihapus pada saldo user
                // Jika tipe aslinya 'penambahan', maka saat dihapus jadi 'pengurangan'
                // Jika tipe aslinya 'pengurangan', maka saat dihapus jadi 'penambahan'
                $reverseTipe = ($transaksi->tipe === 'penambahan') ? 'pengurangan' : 'penambahan';
                self::applyBalanceChange($transaksi, $transaksi->jumlah, $reverseTipe);
            });
        });
    }

    /**
     * Mengelola perubahan saldo pengguna berdasarkan jenis transaksi.
     *
     * @param Transaksi $transaksi Objek transaksi saat ini.
     * @param float $jumlah Jumlah yang akan diterapkan ke saldo.
     * @param string $tipe Tipe transaksi ('penambahan'/'pengurangan').
     * @param int|null $userId Opsional: ID pengguna. Jika null, ambil dari objek $transaksi.
     * @return void
     */
    protected static function applyBalanceChange(Transaksi $transaksi, float $jumlah, string $tipe, ?int $userId = null): void // Pastikan $tipe tidak nullable
    {
        $user = User::find($userId ?? $transaksi->user_id);

        Log::debug("applyBalanceChange called. User ID: " . ($userId ?? $transaksi->user_id) . ", Jumlah: {$jumlah}, Tipe: {$tipe}");

        if ($user) {
            if ($tipe === 'penambahan') {
                $user->saldo += $jumlah;
            } elseif ($tipe === 'pengurangan') {
                $user->saldo -= $jumlah;
            } else {
                Log::warning("Tipe transaksi tidak valid: {$tipe} untuk Transaksi ID: {$transaksi->id}. Saldo user tidak diperbarui.");
                return;
            }

            if ($user->saldo < 0) {
                Log::warning("Saldo negatif dicegah untuk User ID: {$user->id}. Mengatur saldo ke 0.");
                $user->saldo = 0;
            }
            
            Log::debug("applyBalanceChange => User ID: {$user->id}, Old Saldo: " . ($user->getOriginal('saldo') ?? 'N/A') . ", New Saldo: {$user->saldo}");
            
            // Penting: Gunakan saveQuietly() untuk mencegah event loop tak terbatas
            $isSaved = $user->saveQuietly();
            Log::debug("User::saveQuietly() result for User ID {$user->id}: " . ($isSaved ? 'TRUE' : 'FALSE'));
        } else {
            Log::error("User with ID: " . ($userId ?? $transaksi->user_id) . " not found in applyBalanceChange. User saldo not updated.");
        }
    }
}
