<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenarikanResource\Pages;
use App\Models\Penarikan;
use App\Models\User;
use App\Models\Kas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth; // Import Auth facade

class PenarikanResource extends Resource
{
    protected static ?string $model = Penarikan::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $modelLabel = 'Pengajuan Penarikan';
    protected static ?string $pluralModelLabel = 'Pengajuan Penarikan';
    protected static ?string $navigationGroup = 'Riwayat Transaksi';

    public static function form(Form $form): Form
    {
        // Mendapatkan user yang sedang login
        /** @var \App\Models\User $currentUser */ // BARU: Tambahkan ini untuk hint Intelephense
        $currentUser = Auth::user();
        // Mengecek apakah user yang sedang login adalah nasabah
        $isNasabah = $currentUser && $currentUser->hasRole('nasabah');

        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Pengguna')
                    ->relationship('user', 'username')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('jumlah', null))
                    // Set nilai default user_id jika user adalah nasabah
                    ->default(fn () => $isNasabah ? $currentUser->id : null)
                    // Disarankan untuk tetap men-disable ini agar nasabah tidak bisa memilih user lain
                    // ->disabled(fn () => $isNasabah)
                    // Filter opsi user jika user login adalah 'nasabah'
                    ->options(function () use ($isNasabah, $currentUser) {
                        if ($isNasabah) {
                            return [$currentUser->id => $currentUser->username];
                        }
                        return User::pluck('username', 'id'); // Untuk admin, tampilkan semua
                    }),

                Forms\Components\TextInput::make('jumlah')
                    ->label('Jumlah Nominal')
                    ->numeric()
                    ->required()
                    ->prefix('Rp')
                    ->minValue(10000)
                    ->helperText(function (Forms\Get $get) {
                        $userId = $get('user_id');
                        if ($userId) {
                            $user = User::find($userId);
                            return $user ? 'Saldo pengguna: Rp' . number_format($user->saldo, 2, ',', '.') : 'Pilih pengguna untuk melihat saldo.';
                        }
                        return 'Pilih pengguna untuk melihat saldo.';
                    })
                    // BARU: Nonaktifkan field jumlah jika statusnya sudah 'approved'
                    ->disabled(fn (?Penarikan $record) => $record && $record->status === 'approved')
                    ->rules(function (Forms\Get $get, Forms\Set $set, $state) use ($form) {
                        return [
                            'required',
                            'numeric',
                            'min:10000',
                            function (string $attribute, $value, \Closure $fail) use ($form, $get) {
                                $userId = $get('user_id');
                                $user = $userId ? User::find($userId) : null;
                                $amount = (float) $value;

                                $record = $form->getRecord();
                                $originalStatus = $record ? $record->status : null;
                                $currentStatus = $get('status');

                                // Logika untuk pembalikan status dari 'approved'
                                // Ini memungkinkan admin untuk "mengembalikan" saldo jika status diubah dari approved
                                if ($record && $record->exists && $originalStatus === 'approved' &&
                                    ($currentStatus === 'pending' || $currentStatus === 'rejected')) {
                                    return;
                                }

                                if (!$user) {
                                    $fail("Pengguna tidak ditemukan.");
                                    return;
                                }

                                if ($amount > $user->saldo) {
                                    $fail("Jumlah penarikan (Rp" . number_format($amount, 2, ',', '.') . ") melebihi saldo pengguna (Rp" . number_format($user->saldo, 2, ',', '.') . ").");
                                    return;
                                }

                                $kas = Kas::first();

                                if (!$kas) {
                                    $fail("Informasi Kas Utama tidak ditemukan. Hubungi administrator.");
                                    return;
                                }
                                
                                if ($amount > ($kas->total_saldo ?? 0)) {
                                    $fail("Jumlah penarikan (Rp" . number_format($amount, 2, ',', '.') . ") melebihi saldo Kas Utama (Rp" . number_format($kas->total_saldo ?? 0 , 2, ',', '.') . ").");
                                }
                            },
                        ];
                    }),

                Forms\Components\Select::make('status')
                    ->label('Status Pengajuan')
                    ->options([
                        'pending'  => 'Pending',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->default('pending')
                    ->required()
                    ->in(['pending', 'approved', 'rejected'])
                    // Nasabah tidak bisa mengubah status. Admin yang bisa.
                    ->disabled(fn () => $isNasabah)
                    ->columnSpanFull(),

                Forms\Components\DateTimePicker::make('tanggal_pengajuan')
                    ->label('Tanggal Pengajuan')
                    ->default(now())
                    ->disabled()
                    ->required(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        // Mendapatkan user yang sedang login
        /** @var \App\Models\User $currentUser */ // BARU: Tambahkan ini untuk hint Intelephense
        $currentUser = Auth::user();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user.username')
                    ->label('Pengguna')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->money('IDR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('tanggal_pengajuan')
                    ->label('Diajukan Pada')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'pending'  => 'Pending',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),

                // Filter user_id disembunyikan untuk nasabah
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Filter Pengguna')
                    ->relationship('user', 'username')
                    ->searchable()
                    ->preload()
                    ->hidden(fn () => $currentUser?->hasRole('nasabah')), // Gunakan $currentUser di sini
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->hidden(function ($record) use ($currentUser) { // Tambahkan $currentUser di sini
                        return $currentUser?->hasRole('nasabah') && $record->status === 'approved';
                    }),
            
                Tables\Actions\DeleteAction::make()
                    ->hidden(function ($record) use ($currentUser) { // Tambahkan $currentUser di sini
                        return $currentUser?->hasRole('nasabah') && $record->status === 'approved';
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(function () use ($currentUser) { // Tambahkan $currentUser di sini
                            return $currentUser?->hasRole('nasabah');
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    // Ini adalah bagian TERPENTING untuk memfilter data berdasarkan role
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        /** @var \App\Models\User $user */ // BARU: Tambahkan ini untuk hint Intelephense
        $user = Auth::user();

        if ($user && $user->hasRole('nasabah')) {
            // Jika user adalah nasabah, hanya tampilkan pengajuan miliknya
            return $query->where('user_id', $user->id);
        }

        // Untuk user selain nasabah (admin, dll), tampilkan semua pengajuan
        return $query;
    }

    public static function getPages(): array
    {
        // Mendapatkan user yang sedang login
        /** @var \App\Models\User $currentUser */ // BARU: Tambahkan ini untuk hint Intelephense
        $currentUser = Auth::user();

        return [
            'index' => Pages\ListPenarikans::route('/'),
            // 'create' => Pages\CreatePenarikan::route('/create'),
            // 'edit' => Pages\EditPenarikan::route('/{record}/edit'),
        ];
    }
}
