<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany; // Ditambahkan untuk relasi MorphMany
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Penarikan extends Model
{
    use HasFactory;

    protected $table = 'penarikan';

    protected $fillable = [
        'user_id',
        'jumlah',
        'status',
        'tanggal_pengajuan',
        // 'kas_transaksi_id',   // Dihapus dari fillable
        // 'user_transaksi_id',  // Dihapus dari fillable
    ];

    protected $casts = [
        'jumlah'            => 'decimal:2',
        'tanggal_pengajuan' => 'datetime',
        'status'            => 'string',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relasi (Relationships)
    |--------------------------------------------------------------------------
    */

    /**
     * Relasi Many-to-One dengan model User (pemohon penarikan).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi polimorfik ke model Transaksi (saldo user).
     * Penarikan adalah sumber pengurangan saldo user.
     */
    public function transaksiUser(): MorphMany // Mengubah menjadi MorphMany
    {
        return $this->morphMany(Transaksi::class, 'transactable');
    }

    /**
     * Relasi polimorfik ke model KasTransaksi (kas organisasi).
     * Penarikan adalah sumber pengeluaran kas organisasi.
     */
    public function kasTransaksiOrg(): MorphMany // Mengubah menjadi MorphMany
    {
        return $this->morphMany(KasTransaksi::class, 'transaksable');
    }

    /*
    |--------------------------------------------------------------------------
    | Boot Method & Event Listeners
    |--------------------------------------------------------------------------
    | Mengelola transaksi kas dan saldo user secara otomatis saat penarikan
    | disetujui, dibatalkan, atau dihapus.
    */
    protected static function booted(): void
    {
        parent::booted();

        // Event 'updated' akan digunakan untuk mendeteksi perubahan status pengajuan penarikan.
        static::updated(function (Penarikan $penarikan) {
            $originalStatus = $penarikan->getOriginal('status');
            $newStatus = $penarikan->status;

            DB::transaction(function () use ($penarikan, $originalStatus, $newStatus) {
                // Logika utama saat status berubah ke 'approved'
                if ($newStatus === 'approved' && $originalStatus !== 'approved') {
                    Log::debug('Penarikan ID: ' . $penarikan->id . ' status berubah menjadi approved. Memproses transaksi.');
                    self::processApprovedWithdrawal($penarikan);
                }
                // Logika utama saat status berubah dari 'approved' (misal: kembali ke pending/rejected)
                elseif ($originalStatus === 'approved' && ($newStatus === 'pending' || $newStatus === 'rejected')) {
                    Log::debug('Penarikan ID: ' . $penarikan->id . ' status berubah dari approved ke ' . $newStatus . '. Membatalkan transaksi.');
                    self::cancelApprovedWithdrawal($penarikan);
                }
                // Logika saat status tetap 'approved' tapi jumlah berubah (rekonsiliasi)
                elseif ($newStatus === 'approved' && $originalStatus === 'approved' && $penarikan->isDirty('jumlah')) {
                    Log::debug('Penarikan ID: ' . $penarikan->id . ' status tetap approved, jumlah berubah. Rekonsiliasi transaksi.');
                    self::cancelApprovedWithdrawal($penarikan); // Batalkan transaksi lama
                    self::processApprovedWithdrawal($penarikan); // Buat transaksi baru dengan jumlah baru
                }
            });
        });

        // Event 'deleting': Saat record Penarikan dihapus
        static::deleting(function (Penarikan $penarikan) {
            // Batalkan transaksi hanya jika statusnya 'approved' saat dihapus
            if ($penarikan->status === 'approved') {
                Log::debug('Penarikan ID: ' . $penarikan->id . ' dengan status approved sedang dihapus. Membatalkan transaksi terkait.');
                self::cancelApprovedWithdrawal($penarikan);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Metode Pembantu untuk Mengelola Transaksi
    |--------------------------------------------------------------------------
    */

    /**
     * Memproses penarikan yang disetujui:
     * - Membuat record transaksi pengurangan di tabel 'transaksi' (saldo user).
     * - Membuat record transaksi pengeluaran di tabel 'kas_transaksi' (kas organisasi).
     * Ini akan memicu pembaruan saldo di model User dan Kas melalui Model Event mereka.
     *
     * @param Penarikan $penarikan Objek Penarikan yang disetujui.
     */
    protected static function processApprovedWithdrawal(Penarikan $penarikan): void
    {
        // Pastikan kita bekerja dalam transaksi database
        DB::transaction(function () use ($penarikan) {
            $user = User::find($penarikan->user_id);
            $kas = Kas::first(); // Asumsi ada 1 kas pusat

            if (!$user) {
                Log::error('User ID: ' . $penarikan->user_id . ' tidak ditemukan untuk penarikan ID: ' . $penarikan->id);
                return;
            }
            if (!$kas) {
                Log::error('Kas pusat tidak ditemukan untuk penarikan ID: ' . $penarikan->id);
                return;
            }

            // 1. Buat record transaksi di tabel 'transaksi' (saldo user - pengurangan)
            // Cek apakah transaksi user untuk penarikan ini sudah ada
            $userTransaksi = Transaksi::where('transactable_type', Penarikan::class)
                                      ->where('transactable_id', $penarikan->id)
                                      ->first();
            if ($userTransaksi) {
                // Jika sudah ada, update jumlahnya
                $userTransaksi->update([
                    'jumlah' => $penarikan->jumlah,
                    'user_id' => $user->id, // Pastikan user_id juga terupdate jika berubah
                ]);
                Log::debug('User Transaksi ID: ' . $userTransaksi->id . ' diperbarui untuk Penarikan ID: ' . $penarikan->id);
            } else {
                // Jika belum ada, buat yang baru
                $userTransaksi = Transaksi::create([
                    'user_id' => $user->id,
                    'jumlah' => $penarikan->jumlah,
                    'tipe' => 'pengurangan', // Menggunakan 'pengurangan' untuk transaksi user
                    'deskripsi' => 'Pengurangan saldo dari penarikan ID: ' . $penarikan->id . ' (User: ' . $user->username . ')',
                    'transactable_type' => Penarikan::class,
                    'transactable_id' => $penarikan->id,
                ]);
                Log::debug('User Transaksi baru ID: ' . $userTransaksi->id . ' dibuat untuk Penarikan ID: ' . $penarikan->id);
            }

            // 2. Buat record transaksi di tabel 'kas_transaksi' (kas organisasi - pengeluaran)
            // Cek apakah transaksi kas untuk penarikan ini sudah ada
            $kasTransaksi = KasTransaksi::where('transaksable_type', Penarikan::class)
                                        ->where('transaksable_id', $penarikan->id)
                                        ->first();
            if ($kasTransaksi) {
                // Jika sudah ada, update jumlahnya
                $kasTransaksi->update([
                    'kas_id' => $kas->id, // Pastikan kas_id tetap benar
                    'jumlah' => $penarikan->jumlah,
                    'tipe' => 'pengeluaran', // Tipe transaksi untuk kas
                    'deskripsi' => 'Pengeluaran kas untuk penarikan ID: ' . $penarikan->id . ' (User: ' . $user->username . ')',
                ]);
                Log::debug('Kas Transaksi ID: ' . $kasTransaksi->id . ' diperbarui untuk Penarikan ID: ' . $penarikan->id);
            } else {
                // Jika belum ada, buat yang baru
                $kasTransaksi = KasTransaksi::create([
                    'kas_id' => $kas->id,
                    'jumlah' => $penarikan->jumlah,
                    'tipe' => 'pengeluaran', // Tipe transaksi untuk kas
                    'deskripsi' => 'Pengeluaran kas untuk penarikan ID: ' . $penarikan->id . ' (User: ' . $user->username . ')',
                    'transaksable_type' => Penarikan::class,
                    'transaksable_id' => $penarikan->id,
                ]);
                Log::debug('Kas Transaksi baru ID: ' . $kasTransaksi->id . ' dibuat untuk Penarikan ID: ' . $penarikan->id);
            }
        });
    }

    /**
     * Membatalkan penarikan yang disetujui (membalikkan efeknya).
     * Ini akan menghapus record transaksi yang terkait dari tabel 'transaksi' dan 'kas_transaksi'.
     * Penghapusan ini akan memicu event 'deleted' pada model Transaksi dan KasTransaksi,
     * yang akan secara otomatis mengembalikan saldo user dan kas organisasi.
     *
     * @param Penarikan $penarikan Objek Penarikan yang statusnya dibalik atau dihapus.
     */
    protected static function cancelApprovedWithdrawal(Penarikan $penarikan): void
    {
        DB::transaction(function () use ($penarikan) {
            // Hapus Transaksi (saldo user) yang terkait secara polimorfik
            $penarikan->transaksiUser()->get()->each(function (Transaksi $tu) {
                Log::debug("Memanggil delete() pada User Transaksi (ID: {$tu->id}).");
                $tu->delete(); // Ini memicu Transaksi::deleted event
            });
            Log::debug('Semua User Transaksi terkait Penarikan ID: ' . $penarikan->id . ' dihapus.');

            // Hapus KasTransaksi (kas organisasi) yang terkait secara polimorfik
            $penarikan->kasTransaksiOrg()->get()->each(function (KasTransaksi $ktu) {
                Log::debug("Memanggil delete() pada Kas Transaksi (ID: {$ktu->id}).");
                $ktu->delete(); // Ini memicu KasTransaksi::deleted event
            });
            Log::debug('Semua Kas Transaksi terkait Penarikan ID: ' . $penarikan->id . ' dihapus.');
        });
    }
}
