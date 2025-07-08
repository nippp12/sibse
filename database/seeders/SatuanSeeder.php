<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Satuan;

class SatuanSeeder extends Seeder
{
    public function run()
    {
        $satuans = [
            ['nama' => 'Kilogram'],
            ['nama' => 'Pcs'],
            ['nama' => 'Liter'],
        ];

        foreach ($satuans as $satuan) {
            Satuan::updateOrCreate(
                ['nama' => $satuan['nama']],
                []
            );
        }
    }
}
