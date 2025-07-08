<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SampahTransaksiResource\Pages;
use App\Models\SampahTransaksi;
use App\Models\Sampah;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\DatePicker;

class SampahTransaksiResource extends Resource
{
    protected static ?string $model = SampahTransaksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Transaksi Stok Sampah'; // Label navigasi
    protected static ?string $navigationGroup = 'Manajemen Stok'; // Grup navigasi

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Transaksi Stok')
                    ->description('Catat penambahan atau pengurangan stok sampah.')
                    ->columns(2)
                    ->schema([
                        Select::make('sampah_id')
                            ->label('Jenis Sampah')
                            ->relationship('sampah', 'nama')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->reactive() // Penting agar suffix berfungsi
                            ->afterStateHydrated(function (Set $set, Get $get, $state) {
                                // Jika diakses dari tombol "Tambah Stok" di SampahResource, isi otomatis
                                if (request()->has('sampah_id') && is_null($state)) {
                                    $set('sampah_id', request()->query('sampah_id'));
                                }
                                // Isi suffix satuan saat form dimuat/hydrated
                                $sampah = Sampah::find($get('sampah_id'));
                                $set('jumlah_suffix', $sampah->satuan->nama ?? '');
                            })
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                // Update suffix satuan saat sampah_id berubah secara manual
                                $sampah = Sampah::find($state);
                                $set('jumlah_suffix', $sampah->satuan->nama ?? '');
                            }),

                        // Hidden field untuk menyimpan suffix satuan yang akan digunakan oleh TextInput 'jumlah'
                        Hidden::make('jumlah_suffix'),

                        Select::make('tipe')
                            ->label('Tipe Transaksi')
                            ->options([
                                'penambahan'  => 'Penambahan Stok',
                                'pengurangan' => 'Pengurangan Stok',
                            ])
                            ->required()
                            ->native(false)
                            ->placeholder('Pilih tipe transaksi'),

                        TextInput::make('jumlah')
                            ->label('Jumlah')
                            ->numeric()
                            ->step('0.01')
                            // Ambil suffix dari hidden field yang sudah diisi
                            ->suffix(fn (Get $get) => $get('jumlah_suffix') ?? '')
                            ->required()
                            ->placeholder('Masukkan jumlah stok (misal: 10.5)'),

                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Tambahkan deskripsi transaksi (misal: "Pembelian dari pengepul X" atau "Pengiriman ke pabrik Y")')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sampah.nama')
                    ->label('Jenis Sampah')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->colors([
                        'success' => 'penambahan',
                        'danger'  => 'pengurangan',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->formatStateUsing(fn (string $state, SampahTransaksi $record): string =>
                        $state . ' ' . ($record->sampah->satuan->nama ?? ''))
                    ->sortable(),
                Tables\Columns\TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->tooltip(fn (string $state): ?string => strlen($state) > 50 ? $state : null),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Transaksi')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sampah_id')
                    ->label('Jenis Sampah')
                    ->relationship('sampah', 'nama')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('tipe')
                    ->options([
                        'penambahan'  => 'Penambahan',
                        'pengurangan' => 'Pengurangan',
                    ])
                    ->label('Tipe Transaksi'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when($data['from'], fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListSampahTransaksis::route('/'),
            'create' => Pages\CreateSampahTransaksi::route('/create'),
            'edit' => Pages\EditSampahTransaksi::route('/{record}/edit'),
        ];
    }
}