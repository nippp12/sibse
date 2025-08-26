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
use Illuminate\Support\Facades\Gate;
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

    /**
     * Menghitung dan memperbarui total harga dari item sampah yang dikumpulkan.
     *
     * @param Set $set Filament Set instance
     * @param Get $get Filament Get instance
     * @return void
     */
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

    // This function can be static as it doesn't rely on instance properties
    public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float {
        $earthRadius = 6371; // km
    
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
    
        $a = sin($dLat / 2) ** 2 +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) ** 2;
    
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
    
        return round($distance, 2); // hasil dibulatkan ke 2 angka di belakang koma
    }
    
    // This function should also be static if it's not using $this
    public static function validateDistance(Get $get, callable $fail): void {
        $lat1 = $get('latitude');
        $lon1 = $get('longitude');
    
        $lat2 = floatval(env('OFFICE_LATITUDE', -7.719943));
        $lon2 = floatval(env('OFFICE_LONGITUDE', 109.015366));
    
        // Validasi input
        if (!is_numeric($lat1) || !is_numeric($lon1)) {
            return;
        }
    
        // Corrected: Call static method using self::
        $distance = self::calculateDistance((float) $lat1, (float) $lon1, $lat2, $lon2);
    
        if ($distance > 20) {
            $fail("Jarak melebihi 20 km dari kantor ($distance km).");
        }
    }
    

    public static function form(Form $form): Form
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

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
                        ->preload()
                        ->native(false)
                        ->placeholder('Pilih User Pemohon')
                        // Perbaiki penggunaan $user menjadi $currentUser
                        ->hidden(fn (?Pengepulan $record, string $operation): bool => Gate::allows('nasabah') && $operation === 'create')
                        ->disabled(fn (?Pengepulan $record): bool => Gate::allows('nasabah')),

                    Select::make('petugas_id')
                        ->label('Petugas Lapangan')
                        ->relationship('petugas', 'username', function (Builder $query) {
                            // Mengambil petugas yang memiliki peran 'petugas' jika diperlukan
                            // $query->whereHas('roles', fn ($q) => $q->where('name', 'petugas'));
                            // Jika hanya ingin petugas yang sedang login:
                             $query->where('id', Auth::id());
                        })
                        ->searchable()
                        ->preload()
                        ->default(fn () => Auth::id())
                        ->nullable()
                        ->native(false)
                        ->placeholder('Pilih Petugas (Opsional)')
                        // Perbaiki penggunaan $user menjadi $currentUser
                        ->hidden(fn (): bool => $currentUser?->hasRole('nasabah')),

                    Select::make('broadcast_id')
                        ->label('Terkait Broadcast')
                        ->relationship(
                            'broadcast',
                            'judul',
                            fn (Builder $query) => $query->where('jenis', 'pengepulan')
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false)
                        ->placeholder('Pilih Broadcast (Wajib)')
                        ->live()
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
                    TextInput::make('latitude')
                        ->label('Latitude')
                        ->numeric()
                        ->required()
                        ->placeholder('-7.731234')
                        // Removed afterStateUpdated for validation here
                        ->rules([
                            function (Get $get, string $operation) { // $operation can be useful to apply rule conditionally
                                return function (string $attribute, $value, callable $fail) use ($get) {
                                    self::validateDistance($get, $fail);
                                };
                            },
                        ]),
                    
                    TextInput::make('longitude')
                        ->label('Longitude')
                        ->numeric()
                        ->required()
                        ->placeholder('109.007654')
                        // Removed afterStateUpdated for validation here
                        ->rules([
                            function (Get $get, string $operation) {
                                return function (string $attribute, $value, callable $fail) use ($get) {
                                    self::validateDistance($get, $fail);
                                };
                            },
                        ]),
                    

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
                        // Pastikan $currentUser digunakan di sini
                        ->disabled(fn (?Pengepulan $record) => is_null($record) || !$record->exists || $currentUser?->hasRole('nasabah')),

                    DatePicker::make('tanggal')
                        ->label('Tanggal Pengepulan')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->dehydrated()
                        ->rules(['date'])
                        ->readOnly(fn (?Pengepulan $record) => is_null($record) || !$record->exists || $currentUser?->hasRole('nasabah')),
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
                                        $allSampah = Sampah::with('satuan')->get()->pluck('nama', 'id');
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
                                                // Cek harga di SampahTransaksi dengan transactable_id dan transactable_type sesuai pengepulan
                                                $pengepulanId = $get('../../id');
                                                $sampahTransaksi = $sampah->sampahTransaksi()
                                                    ->where('transactable_id', $pengepulanId)
                                                    ->where('transactable_type', \App\Models\Pengepulan::class)
                                                    ->latest()
                                                    ->first();
                                                if ($sampahTransaksi && $sampahTransaksi->harga != 0 && $sampahTransaksi->harga != $sampah->harga) {
                                                    $hargaPerUnit = $sampahTransaksi->harga;
                                                } else {
                                                    $hargaPerUnit = $sampah->harga ?? 0;
                                                }
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
                                    ->required() // Lebih baik tetap wajib, atau pastikan validasi di tempat lain
                                    ->minValue(0) // Tambahkan validasi agar qty tidak bisa minus
                                    // Pastikan $currentUser digunakan di sini
                                    ->readOnly(fn (): bool => $currentUser?->hasRole('nasabah'))
                                    ->default(0)
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
                        ->deleteAction(fn (Forms\Components\Actions\Action $action) => $action->after(fn (Set $set, Get $get) => static::updateTotalHarga($set, $get)))
                        ->reorderAction(fn (Forms\Components\Actions\Action $action) => $action->after(fn (Set $set, Get $get) => static::updateTotalHarga($set, $get))),
                ]),

            Section::make('Ringkasan Keuangan')
                ->description('Total harga yang akan dibayarkan untuk pengepulan ini.')
                ->columns(1)
                ->schema([
                    TextInput::make('total_harga')
                        ->label('Total Harga (Rp)')
                        ->prefix('Rp')
                        ->disabled() // Menggunakan disabled agar tidak bisa diubah langsung
                        ->dehydrated()
                        ->readOnly(), // readOnly juga bisa digunakan untuk indikasi visual yang kuat
                ]),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        return $table
            ->columns([
                TextColumn::make('user.username')->label('User Pemohon')->sortable()->searchable(),
                TextColumn::make('petugas.username')->label('Petugas Lapangan')->default('Belum Ditugaskan')->sortable()->searchable(),
                TextColumn::make('metode_pengambilan')->label('Metode')->formatStateUsing(fn (string $state): string => match ($state) {
                    'jemput' => 'Dijemput',
                    'antar' => 'Diantar',
                })->sortable()->searchable(),
                TextColumn::make('status')->label('Status')->badge()->colors([
                    'warning' => 'pending',
                    'info' => 'diproses',
                    'success' => 'selesai',
                    'danger' => 'dibatalkan',
                ])->sortable(),
                TextColumn::make('total_harga')->label('Total Harga')->money('IDR')->sortable(),
                TextColumn::make('tanggal')->label('Tanggal Pengepulan')->date('d M Y')->sortable(),
                TextColumn::make('created_at')->label('Dibuat Pada')->dateTime('d M Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Terakhir Diupdate')->dateTime('d M Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'diproses' => 'Diproses',
                    'selesai' => 'Selesai',
                    'dibatalkan' => 'Dibatalkan',
                ])->label('Filter Berdasarkan Status'),
                SelectFilter::make('metode_pengambilan')->options([
                    'jemput' => 'Dijemput',
                    'antar' => 'Diantar',
                ])->label('Filter Berdasarkan Metode'),
                Filter::make('tanggal')->form([
                    Forms\Components\DatePicker::make('from')->placeholder('Dari Tanggal'),
                    Forms\Components\DatePicker::make('until')->placeholder('Sampai Tanggal'),
                ])->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['from'], fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date))
                        ->when($data['until'], fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date));
                })->indicateUsing(function (array $data): array {
                    $indicators = [];
                    if ($data['from'] ?? null) {
                        $indicators[] = Tables\Filters\Indicator::make('Tanggal dari ' . Carbon::parse($data['from'])->toFormattedDateString())->removeField('from');
                    }
                    if ($data['until'] ?? null) {
                        $indicators[] = Tables\Filters\Indicator::make('Tanggal sampai ' . Carbon::parse($data['until'])->toFormattedDateString())->removeField('until');
                    }
                    return $indicators;
                }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    // Sembunyikan tombol Edit jika user adalah nasabah dan statusnya 'selesai'
                    ->hidden(fn (Pengepulan $record): bool => $currentUser?->hasRole('nasabah') && $record->status === 'selesai'),
                Tables\Actions\DeleteAction::make()
                    // Sembunyikan tombol Delete jika user adalah nasabah dan statusnya 'selesai'
                    ->hidden(fn (Pengepulan $record): bool => $currentUser?->hasRole('nasabah') && $record->status === 'selesai'),
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
            'create' => Pages\CreatePengepulan::route('/create'),
            'edit' => Pages\EditPengepulan::route('/{record}/edit'), // Sudah dikomentari, biarkan saja
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Mendapatkan user yang sedang login dan memberikan PHPDoc hint
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // Filter berdasarkan user_id jika user adalah 'nasabah'
        // Perhatikan bahwa di form, user_id sudah diset default ke Auth::id()
        // dan field tersebut hidden/disabled untuk nasabah.
        // Jika Anda ingin nasabah hanya melihat data pengepulan miliknya,
        // maka filter ini perlu disesuaikan dengan kolom 'user_id' di tabel 'pengepulans'.
        if ($user && $user->hasRole('nasabah')) {
            return $query->where('user_id', $user->id); // Mengubah 'id' menjadi 'user_id'
        }

        return $query;
    }
}