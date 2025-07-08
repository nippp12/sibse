<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $broadcast_id
 * @property int $user_id
 * @property string $status_kirim
 * @property \Illuminate\Support\Carbon|null $waktu_kirim
 * @property string|null $deskripsi
 * @property string|null $message_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Broadcast $broadcast // <-- TAMBAHKAN INI
 * @property-read \App\Models\User $user           // <-- TAMBAHKAN INI
 */
class BroadcastUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'broadcast_id',
        'user_id',
        'status_kirim',
        'waktu_kirim',
        'deskripsi',
        'message_id',
    ];

    protected $casts = [
        'waktu_kirim' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_SUKSES = 'sukses';
    const STATUS_GAGAL = 'gagal';

    /**
     * Get the broadcast that owns the BroadcastUser.
     */
    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(Broadcast::class);
    }

    /**
     * Get the user that owns the BroadcastUser.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}