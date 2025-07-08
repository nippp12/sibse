<?php

namespace App\Filament\Resources\BroadcastResource\Pages;

use App\Filament\Resources\BroadcastResource;
use Filament\Actions; // Pastikan Actions diimport
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification; // Import Notification untuk pesan feedback
use App\Services\Fonnte; // Import Fonnte service
use App\Models\Broadcast; // Import model Broadcast jika belum ada, untuk type hinting
use Carbon\Carbon; // Import Carbon untuk manipulasi waktu

class EditBroadcast extends EditRecord
{
    protected static string $resource = BroadcastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
