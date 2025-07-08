<?php

namespace App\Filament\Resources\SampahTransaksiResource\Pages;

use App\Filament\Resources\SampahTransaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSampahTransaksi extends EditRecord
{
    protected static string $resource = SampahTransaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
