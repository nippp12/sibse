<?php

namespace App\Filament\Resources\JenisSampahResource\Pages;

use App\Filament\Resources\JenisSampahResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJenisSampahs extends ListRecords
{
    protected static string $resource = JenisSampahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
