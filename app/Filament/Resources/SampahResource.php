<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SampahResource\Pages;
use App\Models\Sampah;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action; // Pastikan ini di-import
use App\Models\User; // Pastikan model User diimpor
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class SampahResource extends Resource
{
    protected static ?string $model = Sampah::class;

    protected static ?string $navigationIcon = 'heroicon-o-trash';
    protected static ?string $navigationLabel = 'Manajemen Sampah';
    protected static ?string $navigationGroup = 'Manajemen Stok';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Dasar Sampah')
                    ->description('Detail utama tentang jenis sampah.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Sampah')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Masukkan nama jenis sampah'),

                        Select::make('jenis_sampah_id')
                            ->label('Jenis Kategori Sampah')
                            ->relationship('jenis', 'nama')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->placeholder('Pilih kategori sampah'),

                        Select::make('satuan_id')
                            ->label('Satuan Unit')
                            ->relationship('satuan', 'nama')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->placeholder('Pilih satuan unit'),

                        TextInput::make('harga')
                            ->label('Harga Jual/Unit')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->step('0.01')
                            ->minValue(0)
                            ->placeholder('Masukkan harga per unit (misal: 2500)'),

                        FileUpload::make('image')
                            ->label('Gambar Sampah')
                            ->image()
                            ->directory('sampah-images')
                            ->nullable()
                            ->columnSpanFull()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ]),
                    ]),

                Section::make('Informasi Stok & Deskripsi')
                    ->description('Rincian stok dan deskripsi tambahan.')
                    ->columns(1)
                    ->schema([
                        TextInput::make('stock')
                            ->label('Stok Saat Ini')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false)
                            ->readOnly()
                            // Tambahkan PHPDoc hint untuk $record di sini
                            ->suffix(fn (?Sampah $record) => $record?->satuan->nama ?? '')
                            // Tambahkan PHPDoc hint untuk $record di sini
                            ->visible(fn (?Sampah $record) => $record?->exists ?? false),

                        Textarea::make('deskripsi')
                            ->label('Deskripsi Detail')
                            ->nullable()
                            ->maxLength(65535)
                            ->rows(4)
                            ->placeholder('Tambahkan deskripsi lengkap mengenai jenis sampah ini...'),
                    ]),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        // Mendapatkan user yang sedang login dan memberikan PHPDoc hint
        /** @var \App\Models\User|null $currentUser */ // PHPDoc hint untuk $currentUser
        $currentUser = Auth::user();
        
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Gambar')
                    ->width(50)
                    ->height(50)
                    ->circular(),

                TextColumn::make('nama')
                    ->label('Nama Sampah')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('jenis.nama')
                    ->label('Kategori')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('stock')
                    ->label('Stok')
                    ->numeric()
                    ->sortable()
                    // Menggunakan $currentUser yang sudah di-hint
                    ->hidden(fn () => $currentUser?->hasRole('nasabah')), 
                    // ->formatStateUsing(fn (string $state, Sampah $record): string =>
                    //      $state . ' ' . ($record->satuan->nama ?? '')),

                TextColumn::make('satuan.nama')
                    ->label('Satuan')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('harga')
                    ->label('Harga/Unit')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->tooltip(fn (string $state): ?string => strlen($state) > 50 ? $state : null),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Terakhir Diupdate')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_image')
                    ->label('Ada Gambar')
                    ->query(fn (Builder $query) => $query->whereNotNull('image')),

                Tables\Filters\SelectFilter::make('jenis_sampah_id')
                    ->label('Filter Kategori')
                    ->relationship('jenis', 'nama')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('add_stock')
                    ->label('Tambah Stok')
                    ->icon('heroicon-o-plus-circle') // Ikon untuk penambahan
                    ->color('success')
                    ->url(fn (Sampah $record): string =>
                        // Pastikan SampahTransaksiResource diimpor jika diperlukan
                        // use App\Filament\Resources\SampahTransaksiResource;
                        \App\Filament\Resources\SampahTransaksiResource::getUrl('create', ['sampah_id' => $record->id])
                    )
                    ->hidden(function () use ($currentUser) { // Menggunakan $currentUser yang sudah di-hint
                        return !$currentUser || ($currentUser instanceof User && $currentUser->hasRole('nasabah'));
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSampahs::route('/'),
            'create' => Pages\CreateSampah::route('/create'),
            'edit' => Pages\EditSampah::route('/{record}/edit'),
        ];
    }
}
