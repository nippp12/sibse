<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JenisSampah;
use App\Models\Satuan;
use App\Models\Sampah;

class SampahSeeder extends Seeder
{
    public function run()
    {
        // Use updateOrCreate to avoid duplicate entries
        $jenisPlastik = JenisSampah::updateOrCreate(
            ['nama' => 'Plastik'],
            ['deskripsi' => 'Sampah jenis plastik']
        );
        $jenisKertas = JenisSampah::updateOrCreate(
            ['nama' => 'Kertas'],
            ['deskripsi' => 'Sampah jenis kertas']
        );

        $satuanKg = Satuan::updateOrCreate(['nama' => 'Kilogram']);
        $satuanPcs = Satuan::updateOrCreate(['nama' => 'Pcs']);

        Sampah::updateOrCreate(
            ['nama' => 'Botol Plastik'],
            [
                'image' => null,
                'jenis_sampah_id' => $jenisPlastik->id,
                'satuan_id' => $satuanPcs->id,
                'harga' => 2000.00,
                'deskripsi' => 'Botol plastik bekas minuman',
            ]
        );

        Sampah::updateOrCreate(
            ['nama' => 'Kertas Bekas'],
            [
                'image' => null,
                'jenis_sampah_id' => $jenisKertas->id,
                'satuan_id' => $satuanKg->id,
                'harga' => 1500.00,
                'deskripsi' => 'Kertas bekas untuk daur ulang',
            ]
        );
    }
}
