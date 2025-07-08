<?php

namespace App\Filament\Resources\PengepulanResource\Pages;

use App\Filament\Resources\PengepulanResource;
use Filament\Actions;
use App\Models\Gudang;
use Filament\Resources\Pages\EditRecord;

class EditPengepulan extends EditRecord
{
    protected static string $resource = PengepulanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
