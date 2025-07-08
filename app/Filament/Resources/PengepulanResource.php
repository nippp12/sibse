<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengepulanResource\Pages;
use App\Models\Pengepulan;
use App\Models\Sampah;
use App\Models\Broadcast;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
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

class PengepulanResource extends Resource
{
    protected static ?string $model = Pengepulan::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Pengepulan Sampah';
    protected static ?string $navigationGroup = 'Transaksi';

    private static function updateTotalHarga(Set $set, Get $get): void
    {
        $items = $get('pengepulanSampah') ?? [];
        $total = 0;
        foreach ($items as $item) {
            $qty = floatval($item['qty'] ?? 0);
            $harga = floatval($item['harga_per_unit'] ?? 0);
            $total += $qty * $harga;
        }
        $set('total_harga', round($total, 2));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Umum Pengepulan')
                ->description('Detail dasar mengenai transaksi pengepulan sampah.')
                ->columns(2)
                ->schema([
                    Select::make('user_id')
                        ->label('User Pemohon')
                        ->relationship('user', 'username')
                        ->required()
                        ->searchable()
                        ->default(fn () => Auth::id())
                        ->native(false)
                        ->placeholder('Pilih User Pemohon'),

                    Select::make('petugas_id')
                        ->label('Petugas Lapangan')
                        ->relationship('petugas', 'username')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->native(false)
                        ->placeholder('Pilih Petugas (Opsional)'),

                    Select::make('broadcast_id')
                        ->label('Terkait Broadcast')
                        ->relationship(
                            'broadcast',
                            'judul',
                            fn (Builder $query) => $query->where('jenis', 'pengepulan') // Filter broadcast jenis 'pengepulan'
                        )
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->native(false)
                        ->placeholder('Pilih Broadcast (Opsional)')
                        ->live() // Aktifkan reaktivitas
                        ->afterStateUpdated(function (?int $state, Get $get, Set $set) {
                            $tanggalOtomatis = Carbon::now()->toDateString(); 
                            $lokasiOtomatis = null; 

                            if ($state) { 
                                $broadcast = Broadcast::find($state);
                                if ($broadcast) { 
                                    if ($broadcast->tanggal_acara) {
                                        $tanggalOtomatis = Carbon::parse($broadcast->tanggal_acara)->toDateString();
                                    }
                                    if ($get('metode_pengambilan') === 'antar' && $broadcast->lokasi) {
                                        $lokasiOtomatis = $broadcast->lokasi;
                                    }
                                }
                            }
                            $set('tanggal', $tanggalOtomatis);
                            $set('lokasi', $lokasiOtomatis);
                        }),

                    Select::make('metode_pengambilan')
                        ->label('Metode Pengambilan')
                        ->options([
                            'jemput' => 'Dijemput',
                            'antar'  => 'Diantar ke Lokasi Drop-off',
                        ])
                        ->required()
                        ->live()
                        ->native(false)
                        ->placeholder('Pilih Metode Pengambilan')
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            if ($get('metode_pengambilan') === 'antar') {
                                $broadcastId = $get('broadcast_id');
                                if ($broadcastId) {
                                    $broadcast = Broadcast::find($broadcastId);
                                    if ($broadcast && $broadcast->lokasi) {
                                        $set('lokasi', $broadcast->lokasi);
                                    } else {
                                        $set('lokasi', null);
                                    }
                                } else {
                                    $set('lokasi', null);
                                }
                            } else {
                                $set('lokasi', null);
                            }
                        }),

                    TextInput::make('lokasi')
                        ->label('Lokasi Penjemputan / Drop-off')
                        ->required(fn (Get $get) => $get('metode_pengambilan') === 'jemput' || ($get('metode_pengambilan') === 'antar' && ! (bool) $get('lokasi')))
                        ->visible(fn (Get $get) => $get('metode_pengambilan') === 'jemput' || $get('metode_pengambilan') === 'antar')
                        ->readOnly(fn (Get $get) => $get('metode_pengambilan') === 'antar' && (bool) $get('broadcast_id'))
                        ->columnSpanFull()
                        ->placeholder('Masukkan alamat lengkap atau titik lokasi')
                        ->hint(fn (Get $get) => $get('metode_pengambilan') === 'antar' ? 'Lokasi akan otomatis terisi sesuai Broadcast jika tersedia dan metode "Diantar ke Lokasi Drop-off" dipilih.' : 'Masukkan alamat lengkap atau titik lokasi penjemputan.'),

                    Select::make('status')
                        ->label('Status Pengepulan')
                        ->options([
                            'pending'    => 'Pending',
                            'diproses'   => 'Diproses',
                            'selesai'    => 'Selesai',
                            'dibatalkan' => 'Dibatalkan',
                        ])
                        ->default('pending')
                        ->required()
                        ->native(false)
                        ->disabled(fn (?Pengepulan $record) => is_null($record) || !$record->exists),

                    DatePicker::make('tanggal')
                        ->label('Tanggal Pengepulan')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->dehydrated()
                        ->rules(['date']),
                ]),

            Section::make('Detail Sampah yang Disetor')
                ->description('Tambahkan jenis sampah beserta kuantitasnya untuk pengepulan ini.')
                ->schema([
                    Repeater::make('pengepulanSampah')
                        ->relationship('pengepulanSampah')
                        ->columnSpanFull()
                        ->defaultItems(1)
                        ->reactive()
                        ->minItems(1)
                        ->collapsible()
                        ->schema([
                            Grid::make(3)->schema([
                                Select::make('sampah_id')
                                    ->label('Jenis Sampah')
                                    ->options(function (Get $get) {
                                        $allSampah = Sampah::with('satuan')->get()->pluck('nama', 'id'); // Ensure 'satuan' is loaded
                                        $selectedIds = collect($get('../../pengepulanSampah'))
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
                                        $set('harga_per_unit', $hargaPerUnit);
                                        $set('qty_suffix', $satuanNama);
                                        static::updateTotalHarga($set, $get);
                                    })
                                    ->afterStateHydrated(function (Set $set, Get $get, $state) {
                                        $hargaPerUnit = 0;
                                        $satuanNama = '';
                                        if ($state) {
                                            $sampah = Sampah::with('satuan')->find($state);
                                            if ($sampah) {
                                                $hargaPerUnit = $sampah->harga ?? 0;
                                                $satuanNama = $sampah->satuan->nama ?? '';
                                            }
                                        }
                                        $set('harga_per_unit', $hargaPerUnit);
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
                                    ->label('Harga/unit')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->readOnly()
                                    ->dehydrated(false),
                            ]),
                            Hidden::make('qty_suffix'),
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
                        ),
                ]),

            Section::make('Ringkasan Keuangan')
                ->description('Total harga yang akan dibayarkan untuk pengepulan ini.')
                ->columns(1)
                ->schema([
                    TextInput::make('total_harga')
                        ->label('Total Harga (Rp)')
                        ->prefix('Rp')
                        ->disabled()
                        ->dehydrated()
                        ->readOnly(),
                ]),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.username')
                    ->label('User Pemohon')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('petugas.username')
                    ->label('Petugas Lapangan')
                    ->default('Belum Ditugaskan')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('metode_pengambilan')
                    ->label('Metode')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'jemput' => 'Dijemput',
                        'antar'  => 'Diantar',
                    })
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'info'    => 'diproses',
                        'success' => 'selesai',
                        'danger'  => 'dibatalkan',
                    ])
                    ->sortable(),

                TextColumn::make('total_harga')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('tanggal')
                    ->label('Tanggal Pengepulan')
                    ->date('d M Y')
                    ->sortable(),

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
                SelectFilter::make('status')
                    ->options([
                        'pending'    => 'Pending',
                        'diproses'   => 'Diproses',
                        'selesai'    => 'Selesai',
                        'dibatalkan' => 'Dibatalkan',
                    ])
                    ->label('Filter Berdasarkan Status'),
                
                SelectFilter::make('metode_pengambilan')
                    ->options([
                        'jemput' => 'Dijemput',
                        'antar'  => 'Diantar',
                    ])
                    ->label('Filter Berdasarkan Metode'),

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
                            $indicators[] = Tables\Filters\Indicator::make('Tanggal dari ' . \Carbon\Carbon::parse($data['from'])->toFormattedDateString())
                                ->removeField('from');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Tanggal sampai ' . \Carbon\Carbon::parse($data['until'])->toFormattedDateString())
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengepulans::route('/'),
            'create' => Pages\CreatePengepulan::route('/create'), // Mengaktifkan kembali halaman Create
            'edit' => Pages\EditPengepulan::route('/{record}/edit'), // Mengaktifkan kembali halaman Edit
        ];
    }
}
