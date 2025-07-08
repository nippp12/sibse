<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Broadcast extends Model
{
    use HasFactory;

    protected $table = 'broadcast'; // Umumnya nama tabel adalah bentuk plural dari nama model, yaitu 'broadcasts'. Pastikan ini sesuai dengan nama tabel Anda.

    protected $fillable = [
        'judul',
        'pesan',
        'jenis',
        'jadwal_kirim',
        'tanggal_acara',
        'lokasi',
        'mention_user',
        'terkirim', // Kolom ini akan otomatis terisi jika Anda menandai broadcast sebagai "terkirim"
        'dibuat_oleh',
    ];

    protected $casts = [
        'jadwal_kirim'  => 'datetime',
        'terkirim'      => 'datetime',
        'tanggal_acara' => 'date',
        'mention_user'  => 'boolean',
    ];

    /**
     * Relasi ke BroadcastUser (tabel pivot) untuk daftar penerima.
     */
    public function broadcastUsers(): HasMany // Ini penting untuk Relation Manager
    {
        return $this->hasMany(BroadcastUser::class);
    }

    /**
     * Dapatkan user yang membuat broadcast ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * Relasi ke pengepulan (jika broadcast jenisnya 'pengepulan').
     * Asumsi: Tabel 'pengepulans' memiliki foreign key 'broadcast_id'.
     */
    public function pengepulans(): HasMany // Nama fungsi relasi sebaiknya plural jika HasMany
    {
        return $this->hasMany(Pengepulan::class);
    }
}