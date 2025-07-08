<?php

namespace App\Filament\Resources\SampahTransaksiResource\Pages;

use App\Filament\Resources\SampahTransaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSampahTransaksis extends ListRecords
{
    protected static string $resource = SampahTransaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
