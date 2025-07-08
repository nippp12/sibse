<?php

namespace App\Filament\Resources\KasResource\Pages;

use App\Filament\Resources\KasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKas extends ListRecords
{
    protected static string $resource = KasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(), // Comment out or remove this line to hide the "New Kas" button
        ];
    }

    // Optional: You could add logic here to redirect if you always want to view the single record
    // instead of a list. For example:
    /*
    public function mount(): void
    {
        parent::mount();
        $kasRecord = \App\Models\Kas::first(); // Or find the specific ID if you have one
        if ($kasRecord) {
            // If you have an 'edit' page for viewing/editing the single record
            // $this->redirect(static::getResource()::getUrl('edit', ['record' => $kasRecord->id]));
            // If you have a custom 'view' page for the single record
            // $this->redirect(static::getResource()::getUrl('view', ['record' => $kasRecord->id]));
        }
    }
    */
}