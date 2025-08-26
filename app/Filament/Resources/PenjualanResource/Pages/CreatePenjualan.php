<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use App\Filament\Resources\PenjualanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

use Illuminate\Validation\ValidationException;
use App\Models\Sampah;

class CreatePenjualan extends CreateRecord
{
    protected static string $resource = PenjualanResource::class;

    protected function beforeSave(): void
    {
        $data = $this->form->getState();

        $penjualanSampahItems = $data['penjualanSampah'] ?? [];

        foreach ($penjualanSampahItems as $index => $item) {
            $sampahId = $item['sampah_id'] ?? null;
            $qty = $item['qty'] ?? 0;

            if ($sampahId) {
                $stock = Sampah::find($sampahId)?->stock ?? 0;
                if ($qty > $stock) {
                    throw ValidationException::withMessages([
                        "penjualanSampah.{$index}.qty" => "Kuantitas tidak boleh melebihi stok yang tersedia ({$stock}) untuk item ini.",
                    ]);
                }
            }
        }
    }

    protected function afterSave(): void
    {
        $data = $this->form->getState();

        $penjualanSampahItems = $data['penjualanSampah'] ?? [];

        foreach ($penjualanSampahItems as $item) {
            $sampahId = $item['sampah_id'] ?? null;
            $qty = $item['qty'] ?? 0;

            if ($sampahId && $qty > 0) {
                $sampah = Sampah::find($sampahId);
                if ($sampah) {
                    // Decrease stock by qty sold
                    $sampah->stock = max(0, $sampah->stock - $qty);
                    $sampah->save();
                }
            }
        }
    }
}
