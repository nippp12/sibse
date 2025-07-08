<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role; // Import model Role dari Spatie

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $modelLabel = 'Manajemen User';
    protected static ?string $pluralModelLabel = 'Manajemen User';
    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('username')
                    ->label('Username')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('alamat')
                    ->label('Alamat')
                    ->maxLength(255),

                Forms\Components\TextInput::make('no_hp')
                    ->label('No HP')
                    ->maxLength(20),

                Forms\Components\TextInput::make('saldo')
                    ->label('Saldo')
                    ->numeric()
                    ->required(),

                // Mengganti Forms\Components\Select::make('role_id')
                // dengan Forms\Components\Select::make('roles') untuk manajemen multi-role Spatie
                Forms\Components\Select::make('roles')
                    ->multiple() // Memungkinkan pemilihan beberapa peran
                    ->relationship('roles', 'name') // Menggunakan relasi 'roles' yang disediakan HasRoles trait
                    ->preload() // Memuat semua peran di awal
                    ->searchable() // Memungkinkan pencarian peran
                    ->label('Roles'), // Label yang lebih sesuai

                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required(fn ($livewire) => $livewire instanceof Pages\CreateUser)
                    ->minLength(8)
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Facades\Hash::make($state))
                    ->dehydrated(fn ($state) => !empty($state)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('username')
                    ->label('Nama')
                    ->sortable()->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('no_hp')
                    ->label('Nomor HP')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('saldo')
                    ->label('Saldo')
                    ->sortable(),
                // Menampilkan nama-nama peran yang dimiliki user (bisa lebih dari satu)
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge() // Menampilkan peran sebagai badge (opsional, visualisasi lebih baik)
                    ->separator(',') // Jika ingin dipisahkan koma
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Anda bisa menambahkan filter berdasarkan peran di sini
                Tables\Filters\SelectFilter::make('roles')->relationship('roles', 'name')
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
            // Jika Anda memiliki relasi tambahan untuk peran atau izin di halaman edit user, tambahkan di sini
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            // 'create' => Pages\CreateUser::route('/create'), // Aktifkan halaman create jika diperlukan
            // 'edit' => Pages\EditUser::route('/{record}/edit'), // Aktifkan halaman edit jika diperlukan
        ];
    }
}
