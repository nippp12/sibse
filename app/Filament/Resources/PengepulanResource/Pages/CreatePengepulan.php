<?php

namespace App\Filament\Resources\PengepulanResource\Pages;

use App\Filament\Resources\PengepulanResource;
use Filament\Actions;
use App\Models\Gudang; // Mungkin tidak terpakai, tapi saya sertakan jika sebelumnya ada
use App\Models\Sampah; // PENTING: Pastikan ini sudah diimport
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // Mungkin tidak terpakai, tapi saya sertakan jika sebelumnya ada

class CreatePengepulan extends CreateRecord
{
    protected static string $resource = PengepulanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currentUser = Auth::user();

        // Pastikan user_id diset untuk 'nasabah'
        if ($currentUser && $currentUser->hasRole('nasabah') && !isset($data['user_id'])) {
            $data['user_id'] = $currentUser->id;
        }

        // Set default status jika belum ada
        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }

        // Hitung total_harga berdasarkan data yang sudah ada.
        // Qty dan harga_per_unit akan dijamin ada oleh mutateRelationshipDataBeforeCreate di Resource.
        $totalHarga = 0;
        foreach ($data['pengepulanSampah'] ?? [] as $i => $item) {
            $qty = floatval($item['qty'] ?? 0);
            $hargaPerUnit = floatval($item['harga_per_unit'] ?? 0); // Pastikan ini juga diambil dari database di Resource

            $totalHarga += $qty * $hargaPerUnit;
        }
        $data['total_harga'] = round($totalHarga, 2);

        return $data;
    }
}