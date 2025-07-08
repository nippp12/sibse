<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BroadcastResource\Pages;
use App\Filament\Resources\BroadcastResource\RelationManagers\BroadcastUsersRelationManager;
use App\Models\Broadcast;
use App\Models\BroadcastUser;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Table;

class BroadcastResource extends Resource
{
    protected static ?string $model = Broadcast::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $modelLabel = 'Pengumuman';
    protected static ?string $pluralModelLabel = 'Pengumuman';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('judul')
                    ->required()
                    ->maxLength(255),
                    // ->columnSpanFull(),

                // Forms\Components\DateTimePicker::make('jadwal_kirim')
                //     ->label('Jadwal Kirim')
                //     ->nullable()
                //     ->helperText('Kosongkan jika ingin segera diatur setelah dibuat. Pengiriman akan diproses oleh sistem.')
                //     // --- PERBAIKAN DI SINI ---
                //     ->minDate(fn (?\Filament\Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) =>
                //         $record?->jadwal_kirim && $record->jadwal_kirim->isPast()
                //             ? null // Izinkan tanggal & waktu lampau jika sudah ada dan di masa lalu
                //             : now() // Jika baru atau belum ada, mulailah dari sekarang (current time)
                //     )
                //     // --- AKHIR PERBAIKAN ---
                //     ->columnSpan(1),

                Forms\Components\Select::make('jenis')
                    ->options([
                        'informasi' => 'Informasi',
                        'pengepulan' => 'Pengepulan',
                        'penarikan' => 'Penarikan',
                    ])
                    ->required()
                    ->native(false)
                    ->columnSpan(1),

                Forms\Components\DatePicker::make('tanggal_acara')
                    ->label('Tanggal Acara (jika ada)')
                    ->nullable()
                    // Ini sudah benar untuk DatePicker (tanggal saja)
                    ->minDate(fn (?\Filament\Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) =>
                        $record?->tanggal_acara && $record->tanggal_acara->isPast()
                            ? null
                            : now()->startOfDay()
                    ),

                Forms\Components\TextInput::make('lokasi')
                    ->label('Lokasi Acara (jika ada)')
                    ->maxLength(255)
                    ->nullable(),

                Forms\Components\Toggle::make('mention_user')
                    ->label('Sertakan @mention pada penerima?')
                    ->default(false)
                    ->helperText('Jika aktif, setiap penerima akan disebutkan di awal pesan (fitur ini harus diaktifkan pada sistem pengiriman).')
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('pesan')
                    ->required()
                    ->maxLength(1000)
                    ->rows(6)
                    ->columnSpanFull()
                    ->helperText('Isi pesan yang akan dikirimkan kepada penerima. Pastikan tidak ada karakter khusus yang akan menyebabkan error pengiriman.'),

                Forms\Components\Hidden::make('dibuat_oleh')
                    ->default(fn () => Auth::id())
                    ->dehydrated(),
            ]),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('judul')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenis')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.username')
                    ->label('Dibuat Oleh')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pesan')
                    ->label('Pesan Broadcast')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis')
                    ->options([
                        'informasi' => 'Informasi',
                        'pengepulan' => 'Pengepulan',
                        'penarikan' => 'Penarikan',
                    ])
                    ->label('Jenis Pengumuman'),
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
            BroadcastUsersRelationManager::class,
        ];
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['dibuat_oleh'] = Auth::id();
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBroadcasts::route('/'),
            // 'create' => Pages\CreateBroadcast::route('/create'),
            'edit' => Pages\EditBroadcast::route('/{record}/edit'),
        ];
    }
}