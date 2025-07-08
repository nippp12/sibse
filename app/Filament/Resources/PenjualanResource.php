<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenjualanResource\Pages;
use App\Models\Penjualan;
use App\Models\Sampah; // Diperlukan untuk detail sampah
use App\Models\User; // Diperlukan untuk user dan petugas
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class PenjualanResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag'; // Icon baru untuk penjualan
    protected static ?string $navigationLabel = 'Penjualan Sampah';
    protected static ?string $navigationGroup = 'Transaksi';

    /**
     * Helper function to calculate and set the total price of the sale.
     * @param Set $set Filament's Set object for updating form fields.
     * @param Get $get Filament's Get object for retrieving form field values.
     */
    private static function updateTotalHarga(Set $set, Get $get): void
    {
        // Get all items from the 'penjualanSampah' repeater
        $items = $get('penjualanSampah') ?? [];
        $total = 0;

        // Iterate through each item to calculate its subtotal and sum them up
        foreach ($items as $item) {
            $qty = floatval($item['qty'] ?? 0);
            // 'harga_per_unit' akan diambil dari state form, yang seharusnya sudah diset
            // dari afterStateUpdated/Hydrated atau input manual user jika editable.
            $harga = floatval($item['harga_per_unit'] ?? 0);
            $total += $qty * $harga;
        }

        // Set the calculated total_harga, rounded to 2 decimal places
        $set('total_harga', round($total, 2));
    }

    /**
     * Defines the form schema for creating and editing Penjualan records.
     * @param Form $form The Filament Form instance.
     * @return Form
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Umum Penjualan')
                ->description('Detail dasar mengenai transaksi penjualan sampah.')
                ->columns(2)
                ->schema([
                    // Petugas adalah user yang membuat/mengedit penjualan
                    Select::make('petugas_id')
                        ->label('Petugas/Admin')
                        ->relationship('petugas', 'username') // Relasi ke User model untuk petugas
                        ->searchable()
                        ->preload()
                        ->required() // Petugas wajib diisi
                        ->default(fn () => Auth::id()) // Default petugas ke user yang login
                        ->native(false)
                        ->placeholder('Pilih Petugas'),

                    DatePicker::make('tanggal')
                        ->label('Tanggal Penjualan')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y') // Format tampilan tanggal
                        ->default(now()), // Default ke tanggal hari ini

                    // KOLOM STATUS UNTUK INTEGRASI TRANSAKSI
                    Select::make('status')
                        ->label('Status Penjualan')
                        ->options([
                            'pending'    => 'Pending',
                            'diproses'   => 'Diproses',
                            'selesai'    => 'Selesai',
                            'dibatalkan' => 'Dibatalkan',
                        ])
                        ->default('pending') // Default status untuk penjualan baru
                        ->required()
                        ->native(false)
                        // Status hanya bisa diubah setelah dibuat. Untuk record baru, status default 'pending'.
                        ->disabled(fn (?Penjualan $record) => is_null($record) || !$record->exists),
                ]),

            Section::make('Detail Sampah yang Dijual')
                ->description('Tambahkan jenis sampah beserta kuantitasnya untuk penjualan ini.')
                ->schema([
                    Repeater::make('penjualanSampah')
                        ->relationship('penjualanSampah') // Relasi ke PenjualanSampah model
                        ->columnSpanFull()
                        ->defaultItems(1)
                        ->reactive() // Membuat repeater reaktif untuk perhitungan otomatis
                        ->minItems(1)
                        ->collapsible() // Dapat dilipat
                        ->schema([
                            Grid::make(3)->schema([
                                Select::make('sampah_id')
                                    ->label('Jenis Sampah')
                                    ->options(function (Get $get) {
                                        $allSampah = Sampah::with('satuan')->get()->pluck('nama', 'id');
                                        $selectedIds = collect($get('../../penjualanSampah'))
                                            ->pluck('sampah_id')
                                            ->filter()
                                            ->values();

                                        $currentId = $get('sampah_id');

                                        return $allSampah->mapWithKeys(function ($nama, $id) use ($selectedIds, $currentId) {
                                            if ($id == $currentId || !$selectedIds->contains($id)) {
                                                return [$id => $nama];
                                            }
                                            return [];
                                        });
                                    })
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->native(false)
                                    ->placeholder('Pilih Jenis Sampah')
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        $hargaPerUnit = 0;
                                        $satuanNama = '';
                                        if ($state) {
                                            $sampah = Sampah::with('satuan')->find($state);
                                            if ($sampah) {
                                                $hargaPerUnit = $sampah->harga ?? 0;
                                                $satuanNama = $sampah->satuan->nama ?? '';
                                            }
                                        }
                                        $set('harga_per_unit', $hargaPerUnit); // Mengatur harga rekomendasi
                                        $set('qty_suffix', $satuanNama);
                                        static::updateTotalHarga($set, $get);
                                    })
                                    ->afterStateHydrated(function (Set $set, Get $get, $state) {
                                        // Saat form dimuat, mengisi harga_per_unit dari pivot table (jika ada) atau dari master Sampah
                                        $hargaPerUnitFromRecord = $get('harga_per_unit');
                                        $satuanNama = '';
                                        $finalHargaPerUnit = $hargaPerUnitFromRecord;

                                        if ($state) {
                                            $sampah = Sampah::with('satuan')->find($state);
                                            if ($sampah) {
                                                $satuanNama = $sampah->satuan->nama ?? '';
                                                // Jika harga dari record belum ada atau 0, ambil dari master Sampah
                                                if (is_null($hargaPerUnitFromRecord) || $hargaPerUnitFromRecord == 0) {
                                                    $finalHargaPerUnit = $sampah->harga ?? 0;
                                                }
                                            }
                                        }
                                        $set('harga_per_unit', $finalHargaPerUnit);
                                        $set('qty_suffix', $satuanNama);
                                    }),

                                TextInput::make('qty')
                                    ->label('Kuantitas')
                                    ->numeric()
                                    ->step('0.01')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn (Set $set, Get $get) => static::updateTotalHarga($set, $get))
                                    ->suffix(fn (Get $get) => $get('qty_suffix') ?? ''),

                                TextInput::make('harga_per_unit')
                                    ->label('Harga/unit Jual') // Diubah labelnya agar lebih jelas
                                    ->prefix('Rp')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->live(), // Ini yang membuat field editable dan reaktif
                                    // DEHYDRATED(FALSE) DIHILANGKAN AGAR TERSIMPAN KE TABEL PIVOT
                            ]),
                            Hidden::make('qty_suffix'), // Hidden field for unit suffix
                        ])
                        ->afterStateUpdated(fn (Set $set, Get $get) => static::updateTotalHarga($set, $get))
                        ->deleteAction(
                            fn (Forms\Components\Actions\Action $action) => $action->after(
                                fn (Set $set, Get $get) => static::updateTotalHarga($set, $get),
                            ),
                        )
                        ->reorderAction(
                            fn (Forms\Components\Actions\Action $action) => $action->after(
                                fn (Set $set, Get $get) => static::updateTotalHarga($set, $get),
                            ),
                        )
                        ->createItemButtonLabel('Tambah Sampah')
                        ->required(),
                ]),

            Textarea::make('deskripsi')
                ->label('Deskripsi Tambahan')
                ->rows(3)
                ->nullable()
                ->columnSpanFull(),

            Section::make('Ringkasan Keuangan')
                ->description('Total harga yang akan diterima dari penjualan ini.')
                ->columns(1)
                ->schema([
                    TextInput::make('total_harga')
                        ->label('Total Harga (Rp)')
                        ->prefix('Rp')
                        ->disabled()
                        ->dehydrated() // Pastikan ini di-dehydrated agar nilai disimpan ke model Penjualan
                        ->readOnly(),
                ]),
        ])->columns(2);
    }

    /**
     * Defines the table schema for listing Penjualan records.
     * @param Table $table The Filament Table instance.
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID Penjualan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('petugas.username')
                    ->label('Petugas/Admin')
                    ->default('N/A')
                    ->sortable()
                    ->searchable(),

                // KOLOM STATUS DI TABEL
                TextColumn::make('status')
                    ->label('Status')
                    ->badge() // Menampilkan status sebagai badge
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'diproses',
                        'success' => 'selesai',
                        'danger' => 'dibatalkan',
                    ])
                    ->sortable(),

                TextColumn::make('total_harga')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('tanggal')
                    ->label('Tanggal Penjualan')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->tooltip(fn (string $state): ?string => strlen($state) > 50 ? $state : null)
                    ->toggleable(isToggledHiddenByDefault: true),

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
                // FILTER STATUS
                SelectFilter::make('status')
                    ->options([
                        'pending'    => 'Pending',
                        'diproses'   => 'Diproses',
                        'selesai'    => 'Selesai',
                        'dibatalkan' => 'Dibatalkan',
                    ])
                    ->label('Filter Berdasarkan Status'),
                    
                Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->placeholder('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->placeholder('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Tanggal dari ' . Carbon::parse($data['from'])->toFormattedDateString())
                                ->removeField('from');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Tanggal sampai ' . Carbon::parse($data['until'])->toFormattedDateString())
                                ->removeField('until');
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
            ]);
    }

    /**
     * Defines the pages for this resource.
     * @return array
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenjualans::route('/'),
            'create' => Pages\CreatePenjualan::route('/create'),
            'edit' => Pages\EditPenjualan::route('/{record}/edit'),
        ];
    }
}
