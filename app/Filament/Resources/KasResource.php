<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KasResource\Pages;
use App\Models\Kas; // Make sure the Kas model is imported
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KasResource extends Resource
{
    protected static ?string $model = Kas::class;

    // Icon that appears in the Filament navigation sidebar.
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    // Navigation label in the sidebar.
    // protected static ?string $navigationLabel = 'Kas Utama';

    protected static ?string $modelLabel = 'Kas Utama';
    protected static ?string $pluralModelLabel = 'Kas Utama';

    // Navigation group in the sidebar.
    protected static ?string $navigationGroup = 'Keuangan';

    /**
     * Defines the form schema for viewing or editing Kas data.
     * Since this is a global balance, 'total_saldo' is typically not edited directly,
     * but rather updated automatically by incoming/outgoing transactions.
     *
     * @param Form $form
     * @return Form
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Field for Total Cash Balance (display only/read-only)
                Forms\Components\TextInput::make('total_saldo')
                    ->label('Total Saldo Kas') // Label in Indonesian
                    ->required()
                    ->numeric()
                    ->prefix('Rp') // Add Rupiah prefix
                    ->readOnly() // Make this field read-only
                    ->disabled() // Ensure the field is non-interactive
                    ->default(0.00), // Default value

                // Field for Last Updated Automatically (display only/read-only)
                Forms\Components\DateTimePicker::make('last_updated')
                    ->label('Terakhir Diperbarui Otomatis') // More informative label
                    ->readOnly() // Read-only
                    ->disabled() // Ensure the field is non-interactive
                    ->nullable(), // Can be null
            ]);
    }

    /**
     * Defines the columns for the Kas data table display.
     *
     * @param Table $table
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Column to display Total Cash Balance
                Tables\Columns\TextColumn::make('total_saldo')
                    ->label('Total Saldo Kas') // Label in Indonesian
                    ->money('IDR', true)
                    ->sortable(),

                // Column to display Last Updated Time
                Tables\Columns\TextColumn::make('last_updated')
                    ->label('Terakhir Diperbarui') // Label in Indonesian
                    ->dateTime() // Format as date and time
                    ->sortable(),

                // created_at column (cash record creation time)
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Can be hidden by default

                // updated_at column (last cash record update time)
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Can be hidden by default
            ])
            ->filters([
                // Filters are not common for a single record, can be removed if there's no specific need.
            ])
            ->actions([
                // Disable Edit action so it cannot be edited
                // Tables\Actions\EditAction::make(),
                // Delete action removed because there should only be one global Kas record.
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Bulk actions removed because there should only be one global Kas record.
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->headerActions([
                // Create action removed because there should only be one global Kas record.
                // Tables\Actions\CreateAction::make(), // This line should be commented out or removed
            ]);
    }

    /**
     * Defines any relationships for this resource (if any).
     * For example, a relationship to KasTransaksi.
     *
     * @return array
     */
    public static function getRelations(): array
    {
        return [
            // Example if there's a RelationManager for KasTransaksi:
            // RelationManagers\KasTransaksiRelationManager::class,
        ];
    }

    /**
     * Defines the pages used by this resource.
     *
     * @return array
     */
    public static function getPages(): array
    {
        return [
            // Only 'index' page. The 'create' page is explicitly removed.
            'index' => Pages\ListKas::route('/'),
            // 'edit' => Pages\EditKas::route('/{record}/edit'), // Kas edit/view page
            // 'create' page disabled because there is only one Kas record.
            // 'create' => Pages\CreateKas::route('/create'), // <--- Ensure this is commented out or removed
        ];
    }

    /**
     * Returns the Eloquent query for this resource.
     * Limited to 1 record because this is a single global cash.
     *
     * @return Builder
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->limit(1);
    }

    // The getUrl() method causing compatibility errors has been REMOVED from here.
    // The logic for redirecting to a single Kas record is now in ListKas.php.
}