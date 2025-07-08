<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenarikanResource\Pages;
use App\Models\Penarikan;
use App\Models\User; // Import model User
use App\Models\Kas;  // Import model Kas
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rule; // Pastikan Rule di-import jika diperlukan

class PenarikanResource extends Resource
{
    protected static ?string $model = Penarikan::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    // protected static ?string $navigationLabel = 'Penarikan Dana';
    protected static ?string $navigationGroup = 'Riwayat Transaksi';

    // Menyesuaikan label model untuk breadcrumbs dan UI lainnya
    protected static ?string $modelLabel = 'Pengajuan Penarikan';
    protected static ?string $pluralModelLabel = 'Pengajuan Penarikan'; // Ini akan mempengaruhi breadcrumb "Penarikans" menjadi "Penarikan Dana"

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Pengguna')
                    ->relationship('user', 'username')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive() // Penting agar field jumlah bisa bereaksi terhadap perubahan user
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('jumlah', null)), // Kosongkan jumlah saat user berubah

                Forms\Components\TextInput::make('jumlah')
                    ->label('Jumlah Nominal')
                    ->numeric()
                    ->required()
                    ->prefix('Rp')
                    ->minValue(10000) // Minimal penarikan Rp 10.000
                    ->helperText(function (Forms\Get $get) {
                        $userId = $get('user_id');
                        if ($userId) {
                            $user = User::find($userId);
                            return $user ? 'Saldo pengguna: Rp' . number_format($user->saldo, 2, ',', '.') : 'Pilih pengguna untuk melihat saldo.';
                        }
                        return 'Pilih pengguna untuk melihat saldo.';
                    })
                    // Mengembalikan logika validasi langsung ke dalam rules()
                    ->rules(function (Forms\Get $get, Forms\Set $set, $state) use ($form) { // Tambah $set dan $state
                        return [
                            'required',
                            'numeric',
                            'min:10000',
                            // Aturan validasi kustom sebagai closure langsung
                            function (string $attribute, $value, \Closure $fail) use ($form, $get) {
                                $userId = $get('user_id'); // Ambil user_id dari form state
                                $user = $userId ? User::find($userId) : null;
                                $amount = (float) $value; // Nilai 'jumlah' yang sedang divalidasi

                                $record = $form->getRecord(); // Dapatkan record yang sedang diedit (jika ada)

                                // Dapatkan status LAMA dari record yang dimuat (sebelum perubahan form)
                                $originalStatus = $record ? $record->status : null;
                                // Dapatkan status BARU dari input form
                                $currentStatus = $get('status');

                                // Cek apakah ini adalah pengeditan record yang sudah ada
                                // DAN statusnya sedang dibalik dari 'approved' menjadi 'pending' atau 'rejected'
                                if ($record && $record->exists && $originalStatus === 'approved' &&
                                    ($currentStatus === 'pending' || $currentStatus === 'rejected')) {
                                    // Jika kondisi ini terpenuhi, lewati validasi saldo karena ini adalah pembalikan
                                    return;
                                }

                                // --- Validasi normal (jika bukan pembalikan dari 'approved') ---

                                // Validasi: Saldo pengguna harus cukup
                                if ($user && $amount > $user->saldo) {
                                    $fail("Jumlah penarikan (Rp" . number_format($amount, 2, ',', '.') . ") melebihi saldo pengguna (Rp" . number_format($user->saldo, 2, ',', '.') . ").");
                                }

                                // Validasi: Saldo kas harus cukup
                                $kas = Kas::first(); // Ambil record Kas global
                                if (!$kas) {
                                    $fail("Informasi Kas Utama tidak ditemukan. Hubungi administrator.");
                                    return; // Penting untuk return setelah $fail jika tidak ingin melanjutkan validasi
                                }
                                if ($amount > $kas->total_saldo) {
                                    $fail("Jumlah penarikan (Rp" . number_format($amount, 2, ',', '.') . ") melebihi saldo Kas Utama (Rp" . number_format($kas->total_saldo, 2, ',', '.') . ").");
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
                    ->disabledOn('create') // Admin yang harus mengubah status, bukan pengguna yang membuat pengajuan
                    ->columnSpanFull(),

                Forms\Components\DateTimePicker::make('tanggal_pengajuan')
                    ->label('Tanggal Pengajuan')
                    ->default(now())
                    ->disabled() // Tidak bisa diubah
                    ->required(),

                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Dibuat Pada')
                    ->disabled()
                    ->hiddenOn('create')
                    ->hiddenOn('edit'),
                Forms\Components\DateTimePicker::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->disabled()
                    ->hiddenOn('create')
                    ->hiddenOn('edit'),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
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

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Filter Pengguna')
                    ->relationship('user', 'username')
                    ->searchable()
                    ->preload(),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenarikans::route('/'),
            'create' => Pages\CreatePenarikan::route('/create'),
            'edit' => Pages\EditPenarikan::route('/{record}/edit'),
        ];
    }
}
