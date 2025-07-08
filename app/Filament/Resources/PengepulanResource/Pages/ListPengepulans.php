<?php

namespace App\Filament\Resources\PengepulanResource\Pages;

use App\Filament\Resources\PengepulanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPengepulans extends ListRecords
{
    protected static string $resource = PengepulanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
