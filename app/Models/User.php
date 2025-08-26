<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Traits\HasRoles; // Pastikan ini diimpor dengan benar

/**
 * @mixin \Spatie\Permission\Traits\HasRoles
 * 
 * @method bool hasRole(string|array $roles)
 */
class User extends Authenticatable // Perhatikan, tidak perlu implement MustVerifyEmail jika tidak digunakan
{
    use HasFactory, Notifiable, HasRoles;

    protected $table = 'users'; // Nama tabel sudah benar

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'alamat',
        'no_hp',
        'saldo',
        // 'role_id', // Ini harus ada di fillable jika Anda mengelola role via role_id
                      // Namun, jika Anda menggunakan Spatie\Permission, biasanya role tidak disimpan di kolom role_id
                      // melainkan melalui tabel pivot roles. Jadi, pastikan ini sesuai dengan implementasi Anda.
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'saldo' => 'decimal:2', // Penting: tambahkan casting untuk kolom saldo
    ];

    /**
     * Accessor untuk mengembalikan username sebagai "name" untuk Filament atau tujuan lain.
     * Filament sering mencari atribut 'name' secara default.
     *
     * @return string
     */
    public function getNameAttribute(): string
    {
        return $this->username ?? '';
    }

    /**
     * Relasi Many-to-One dengan model Role dari Spatie.
     * Seorang pengguna memiliki satu role.
     * Ini mendukung Forms\Components\Select::make('role_id')->relationship('role', 'name')
     * dan Tables\Columns\TextColumn::make('role.name') di UserResource.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role(): BelongsTo
    {
        // Pastikan kelas Role yang benar digunakan jika ada namespace lain
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'role_id');
    }

    /**
     * Relasi One-to-Many dengan model Transaksi.
     * Seorang pengguna bisa memiliki banyak catatan transaksi saldo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'user_id');
    }

    /**
     * Relasi One-to-Many dengan model Pengepulan.
     * Seorang pengguna bisa membuat banyak permintaan pengepulan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pengepulanPengguna(): HasMany
    {
        return $this->hasMany(Pengepulan::class, 'user_id');
    }

    /**
     * Relasi One-to-Many dengan model Pengepulan sebagai petugas.
     * Seorang pengguna (sebagai petugas) bisa menangani banyak pengepulan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pengepulanPetugas(): HasMany
    {
        return $this->hasMany(Pengepulan::class, 'petugas_id');
    }

    // Relasi Penarikan, Broadcast, dan BroadcastUser yang sudah Anda definisikan:

    /**
     * Relasi One-to-Many dengan model Penarikan.
     * Seorang pengguna bisa memiliki banyak catatan penarikan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function penarikan(): HasMany
    {
        return $this->hasMany(Penarikan::class); // Asumsi foreign key adalah user_id
    }

    /**
     * Relasi One-to-Many dengan model Broadcast.
     * Seorang pengguna bisa membuat banyak broadcast.
     * 'dibuat_oleh' adalah foreign key di tabel broadcasts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function broadcasts(): HasMany
    {
        return $this->hasMany(Broadcast::class, 'dibuat_oleh');
    }

    /**
     * Relasi One-to-Many dengan model BroadcastUser.
     * Seorang pengguna bisa terkait dengan banyak broadcast melalui tabel pivot/menengah.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function broadcastUsers()
    {
        return $this->hasMany(BroadcastUser::class, 'user_id');
    }
}
