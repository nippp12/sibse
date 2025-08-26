<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User; // Pastikan model User diimpor dengan benar
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role; // Pastikan model Role diimpor jika digunakan secara langsung

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $modelLabel = 'Manajemen User';
    protected static ?string $pluralModelLabel = 'Manajemen User';
    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form->schema([
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
                ->required()
                // Tambahkan PHPDoc hint untuk $record agar hasRole dikenali
                ->disabled(fn(?User $record) => $record?->hasRole('nasabah')),

            Forms\Components\Select::make('roles')
                ->label('Roles')
                ->multiple()
                ->relationship('roles', 'name')
                ->preload()
                ->searchable()
                // Tambahkan PHPDoc hint untuk $record agar hasRole dikenali
                ->hidden(fn(?User $record) => $record?->hasRole('nasabah')),

            Forms\Components\TextInput::make('password')
                ->label('Password')
                ->password()
                ->required(fn($livewire) => $livewire instanceof Pages\CreateUser)
                ->minLength(8)
                ->maxLength(255)
                ->dehydrateStateUsing(fn($state) => bcrypt($state))
                ->dehydrated(fn($state) => filled($state)),
        ]);
    }

    public static function table(Table $table): Table
    {
        // Mendapatkan user yang sedang login dan memberikan PHPDoc hint
        /** @var \App\Models\User|null $loggedInUser */
        $loggedInUser = Auth::user();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('username')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('no_hp')
                    ->label('No HP')
                    ->sortable()
                    ->searchable(),

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

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(',')
                    ->sortable()
                    ->searchable(),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')->relationship('roles', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    // Menggunakan $loggedInUser yang sudah di-hint
                    ->hidden(fn(?User $record) => $loggedInUser?->hasRole('nasabah') && $loggedInUser?->id !== $record->id),

                Tables\Actions\DeleteAction::make()
                    // Menggunakan $loggedInUser yang sudah di-hint
                    ->hidden(fn(?User $record) => $loggedInUser?->hasRole('nasabah')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        // Menggunakan $loggedInUser yang sudah di-hint
                        ->hidden(fn() => $loggedInUser?->hasRole('nasabah')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Mendapatkan user yang sedang login dan memberikan PHPDoc hint
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user && $user->hasRole('nasabah')) {
            return $query->where('id', $user->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            // 'create' => Pages\CreateUser::route('/create'),
            // 'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
