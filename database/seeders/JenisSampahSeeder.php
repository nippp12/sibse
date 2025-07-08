<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JenisSampah;

class JenisSampahSeeder extends Seeder
{
    public function run()
    {
        $jenisSampahs = [
            ['nama' => 'Plastik', 'deskripsi' => 'Sampah jenis plastik'],
            ['nama' => 'Kertas', 'deskripsi' => 'Sampah jenis kertas'],
            ['nama' => 'Logam', 'deskripsi' => 'Sampah jenis logam'],
        ];

        foreach ($jenisSampahs as $jenis) {
            JenisSampah::updateOrCreate(
                ['nama' => $jenis['nama']],
                ['deskripsi' => $jenis['deskripsi']]
            );
        }
    }
}
