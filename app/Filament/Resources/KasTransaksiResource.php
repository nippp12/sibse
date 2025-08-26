<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KasTransaksiResource\Pages;
use App\Models\KasTransaksi;
use App\Models\Kas; // Import model Kas
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KasTransaksiResource extends Resource
{
    protected static ?string $model = KasTransaksi::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    // protected static ?string $navigationLabel = 'Jurnal Kas';
    protected static ?string $modelLabel = 'Transaksi Kas';
    protected static ?string $pluralModelLabel = 'Transaksi Kas';
    protected static ?string $navigationGroup = 'Keuangan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Field kas_id sekarang disembunyikan sepenuhnya dari form
                // Karena akan diisi secara otomatis di mutateFormDataBeforeCreate/Save
                Forms\Components\Hidden::make('kas_id')
                    ->default(function () {
                        // Pastikan ada record Kas global, jika tidak, buat dulu
                        $kas = Kas::firstOrCreate([], ['total_saldo' => 0, 'last_updated' => now()]);
                        return $kas->id;
                    })
                    ->required(), // Tetap required untuk validasi internal Filament

                // Field untuk Jumlah Nominal Transaksi Kas
                Forms\Components\TextInput::make('jumlah')
                    ->label('Jumlah Nominal')
                    ->numeric()
                    ->required()
                    ->prefix('Rp')
                    ->minValue(0),

                // Field untuk Tipe Transaksi Kas (Pemasukan/Pengeluaran)
                Forms\Components\Select::make('tipe')
                    ->label('Tipe Transaksi')
                    ->options([
                        'pemasukan' => 'Pemasukan',
                        'pengeluaran' => 'Pengeluaran',
                    ])
                    ->required(),

                // Field untuk Deskripsi Transaksi Kas
                Forms\Components\Textarea::make('deskripsi')
                    ->label('Deskripsi')
                    ->maxLength(255)
                    ->nullable(),

                // Field untuk Tanggal Transaksi
                // Forms\Components\DateTimePicker::make('tanggal')
                //     ->label('Tanggal Transaksi')
                //     ->default(now())
                //     ->required(),

                // Created at dan Updated at (tetap read-only)
                // Forms\Components\DateTimePicker::make('created_at')
                //     ->label('Dibuat Pada')
                //     ->disabled()
                //     ->hiddenOn('create')
                //     ->hiddenOn('edit'),
                // Forms\Components\DateTimePicker::make('updated_at')
                //     ->label('Terakhir Diperbarui')
                //     ->disabled()
                //     ->hiddenOn('create')
                //     ->hiddenOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\TextColumn::make('id')
                //     ->label('ID')
                //     ->sortable()
                //     ->searchable()
                //     ->toggleable(isToggledHiddenByDefault: true),

                // Kolom untuk menampilkan Saldo Kas Utama (dari relasi Kas)
                // Tables\Columns\TextColumn::make('kas.total_saldo')
                //     ->label('Saldo Kas Utama')
                //     ->money('IDR', true)
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->money('IDR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipe')
                    ->label('Tipe')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'pemasukan' => 'success',
                        'pengeluaran' => 'danger',
                        default => 'gray',
                    }),
                    // ->tooltip(fn (string $state): string => match ($state) {
                    //     'pemasukan' => 'Transaksi Pemasukan',
                    //     'pengeluaran' => 'Transaksi Pengeluaran',
                    //     default => 'Tipe Tidak Diketahui',
                    // }),

                Tables\Columns\TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->toggleable(),

                // Tables\Columns\TextColumn::make('tanggal')
                //     ->label('Tanggal Transaksi')
                //     ->dateTime()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipe')
                    ->label('Filter Tipe')
                    ->options([
                        'pemasukan' => 'Pemasukan',
                        'pengeluaran' => 'Pengeluaran',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->placeholder('Dari Tanggal'),
                        \Filament\Forms\Components\DatePicker::make('until')->placeholder('Sampai Tanggal'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when($data['from'], fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Tanggal dari ' . \Carbon\Carbon::parse($data['from'])->toFormattedDateString())->removeField('from');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = \Filament\Tables\Filters\Indicator::make('Tanggal sampai ' . \Carbon\Carbon::parse($data['until'])->toFormattedDateString())->removeField('until');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            // ->headerActions([
            //     Tables\Actions\CreateAction::make(),
            // ])
            ;
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
            'index' => Pages\ListKasTransaksis::route('/'),
            // 'create' => Pages\CreateKasTransaksi::route('/create'),
            // 'edit' => Pages\EditKasTransaksi::route('/{record}/edit'),
        ];
    }

    /**
     * Mutasi data form sebelum disimpan (untuk pembuatan baru).
     * Pastikan 'kas_id' selalu terisi.
     *
     * @param array $data
     * @return array
     */
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        // Temukan record Kas pertama. Jika tidak ada, buat satu untuk inisialisasi.
        $kas = Kas::firstOrCreate([], ['total_saldo' => 0, 'last_updated' => now()]);
        $data['kas_id'] = $kas->id; // Pastikan kas_id terisi

        return $data;
    }

}
