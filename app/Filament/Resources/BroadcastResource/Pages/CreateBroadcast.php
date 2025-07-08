<?php

namespace App\Filament\Resources\BroadcastResource\Pages;

use App\Filament\Resources\BroadcastResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions; // Import Actions
use Filament\Notifications\Notification; // Import Notification
use App\Services\Fonnte; // Import Fonnte service
use App\Models\Broadcast; // Import Broadcast model if needed for type hinting

class CreateBroadcast extends CreateRecord
{
    protected static string $resource = BroadcastResource::class;

    protected function getFormActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
        // // Pastikan Anda memanggil parent::getFormActions() untuk menyertakan aksi default (seperti "Create")
        // $defaultActions = parent::getFormActions();

        // // Tambahkan aksi kustom "Kirim Sekarang" di sini
        // $sendNowAction = Actions\Action::make('send_now')
        //     ->label('Kirim Sekarang')
        //     ->color('success')
        //     ->icon('heroicon-o-paper-airplane')
        //     ->requiresConfirmation()
        //     ->modalHeading('Konfirmasi Pengiriman Broadcast')
        //     ->modalDescription('Apakah Anda yakin ingin mengirim broadcast ini sekarang ke semua penerima yang terdaftar? Ini akan mengirim pesan secara instan, mengabaikan jadwal yang ditentukan.')
        //     ->modalSubmitActionLabel('Ya, Kirim Sekarang')
        //     // Tombol hanya terlihat setelah record dibuat dan belum terkirim
        //     // Untuk halaman 'Create', tombol ini mungkin baru relevan setelah record disimpan,
        //     // atau Anda bisa menyembunyikannya pada halaman create dan hanya menampilkannya di halaman edit.
        //     // Untuk tujuan ini, saya akan membuatnya terlihat hanya pada halaman edit.
        //     // Jika Anda tetap ingin di halaman create, logikanya akan lebih kompleks karena record belum ada.
        //     ->visible(fn (?Broadcast $record) => $record && ($record->terkirim === null || $record->terkirim->isPast()))
        //     ->action(function (Broadcast $record, Fonnte $fonnteService) {
        //         // Logika pengiriman pesan yang sama seperti yang sudah Anda miliki
        //         $messagesData = [];
        //         $successfulSends = 0;

        //         if ($record->broadcastUsers->isEmpty()) {
        //             Notification::make()->title('Tidak Ada Penerima')->body('Broadcast ini tidak memiliki penerima yang terdaftar untuk dikirim.')->danger()->send();
        //             return;
        //         }

        //         foreach ($record->broadcastUsers as $broadcastUser) {
        //             $user = $broadcastUser->user;
        //             if ($user && $user->whatsapp_number) {
        //                 $cleanMessage = strip_tags($record->pesan);
        //                 $messagesData[] = [
        //                     'target' => $user->whatsapp_number,
        //                     'message' => $cleanMessage,
        //                     'delay' => '1-5',
        //                 ];
        //             } else {
        //                 Notification::make()->title('Peringatan Pengiriman')->body("Penerima '{$user->username}' tidak memiliki nomor WhatsApp yang valid. Pesan tidak akan dikirimkan kepadanya.")->warning()->send();
        //             }
        //         }

        //         if (empty($messagesData)) {
        //             Notification::make()->title('Gagal Mengirim')->body('Tidak ada nomor WhatsApp yang valid atau pesan untuk dikirim.')->danger()->send();
        //             return;
        //         }

        //         try {
        //             $response = $fonnteService->sendBatchMessages($messagesData, ['sequence' => true]);

        //             if (isset($response['status']) && $response['status'] === true) {
        //                 foreach ($record->broadcastUsers as $broadcastUser) {
        //                     $broadcastUser->status_kirim = 'sent';
        //                     $broadcastUser->waktu_kirim = now();
        //                     $broadcastUser->save();
        //                     $successfulSends++;
        //                 }
        //                 $record->terkirim = now();
        //                 $record->save();

        //                 Notification::make()->title('Broadcast Terkirim!')->body("Pesan broadcast berhasil dikirim ke {$successfulSends} penerima.")->success()->send();
        //             } else {
        //                 $errorMessage = $response['message'] ?? 'Terjadi kesalahan tidak diketahui saat mengirim broadcast.';
        //                 Notification::make()->title('Gagal Mengirim Broadcast')->body("Fonnte API merespons dengan kesalahan: {$errorMessage}")->danger()->send();
        //                 foreach ($record->broadcastUsers as $broadcastUser) {
        //                     if ($broadcastUser->status_kirim !== 'sent') {
        //                         $broadcastUser->status_kirim = 'failed';
        //                         $broadcastUser->save();
        //                     }
        //                 }
        //             }
        //         } catch (\Exception $e) {
        //             Notification::make()->title('Error Sistem')->body('Terjadi kesalahan sistem saat menghubungi Fonnte API: ' . $e->getMessage())->danger()->send();
        //             foreach ($record->broadcastUsers as $broadcastUser) {
        //                 if ($broadcastUser->status_kirim !== 'sent') {
        //                     $broadcastUser->status_kirim = 'failed';
        //                     $broadcastUser->save();
        //                 }
        //             }
        //         }
        //         $record->refresh();
        //     });

        // // Gabungkan aksi default dengan aksi kustom Anda
        // return array_merge($defaultActions, [$sendNowAction]);
    }
}