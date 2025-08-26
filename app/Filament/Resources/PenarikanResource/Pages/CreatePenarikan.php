<?php

namespace App\Filament\Resources\PenarikanResource\Pages;

use App\Filament\Resources\PenarikanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth; // PENTING: Tambahkan ini untuk mengakses user yang sedang login

class CreatePenarikan extends CreateRecord
{
    protected static string $resource = PenarikanResource::class;

    /**
     * Mutate the form data before creating the record.
     * This method is executed on the server-side before the record is saved to the database.
     * It's crucial for setting values that might be disabled in the form but are required.
     *
     * @param array $data The data array from the form.
     * @return array The mutated data array.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currentUser = Auth::user(); // Dapatkan user yang sedang login

        // Jika user yang sedang login memiliki role 'nasabah',
        // pastikan 'user_id' diatur ke ID user tersebut.
        // Ini mengatasi masalah field 'user_id' yang disabled tidak terkirim.
        if ($currentUser && $currentUser->hasRole('nasabah')) {
            $data['user_id'] = $currentUser->id;
        }

        // Opsional: Memastikan 'tanggal_pengajuan' terisi jika belum ada.
        // Ini sebagai fallback jika default di form tidak bekerja atau dihilangkan.
        if (!isset($data['tanggal_pengajuan'])) {
            $data['tanggal_pengajuan'] = now();
        }

        // Opsional: Memastikan 'status' default 'pending' jika belum ada.
        // Ini sebagai fallback jika default di form tidak bekerja atau dihilangkan.
        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }

        return $data; // Kembalikan data yang sudah dimodifikasi
    }

    // Anda juga bisa secara opsional menambahkan metode authorizeAccess()
    // untuk mengontrol siapa yang bisa mengakses halaman pembuatan ini.
    // Contoh: Hanya admin dan nasabah yang bisa.
    // protected function authorizeAccess(): void
    // {
    //     $user = Auth::user();
    //     if (!$user || !($user->hasRole('admin') || $user->hasRole('nasabah'))) {
    //         abort(403, 'Unauthorized access.'); // Atau redirect ke halaman lain
    //     }
    // }
}