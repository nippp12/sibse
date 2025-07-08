<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisSampah extends Model
{
    use HasFactory;

    protected $table = 'jenis_sampah';

    protected $fillable = [
        'nama',
        'deskripsi',
    ];

    public $timestamps = true;

    public function sampahs()
    {
        return $this->hasMany(Sampah::class, 'jenis_sampah_id');
    }
}
