<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiResource\Pages;
use App\Filament\Resources\TransaksiResource\RelationManagers;
use App\Models\Transaksi; // Pastikan model Transaksi di-import
use App\Models\User; // Import model User untuk relasi
use Filament\Forms;
use Filament\Forms\Form; // Pastikan menggunakan Filament\Forms\Form
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope; // Jika tidak menggunakan soft delete, bisa dihapus

class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;

    // Icon yang muncul di sidebar navigasi Filament
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    // Label navigasi di sidebar
    protected static ?string $navigationLabel = 'Riwayat Transaksi';

    // Grup navigasi di sidebar (misalnya 'Keuangan')
    protected static ?string $navigationGroup = 'Riwayat Transaksi';
    protected static ?string $modelLabel = 'Riwayat Transaksi';
    protected static ?string $pluralModelLabel = 'Riwayat Transaksi'; // Ini akan mempengaruhi breadcrumb "Penarikans" menjadi "Penarikan Dana"

    /**
     * Mendefinisikan skema form untuk membuat atau mengedit data Transaksi.
     *
     * @param Form $form
     * @return Form
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Field untuk memilih User (Foreign Key)
                Forms\Components\Select::make('user_id')
                    ->label('Pengguna') // Label dalam bahasa Indonesia
                    ->relationship('user', 'username') // Menampilkan username dari model User
                    ->searchable() // Memungkinkan pencarian user
                    ->preload() // Memuat semua pilihan di awal (hati-hati jika user sangat banyak)
                    ->required(), // Wajib diisi

                // Field untuk Jumlah Transaksi
                Forms\Components\TextInput::make('jumlah')
                    ->label('Jumlah Nominal') // Label dalam bahasa Indonesia
                    ->numeric() // Hanya menerima input angka
                    ->required() // Wajib diisi
                    ->default(0.00) // Nilai default
                    ->prefix('Rp') // Menambahkan prefix Rupiah
                    ->minValue(0), // Jumlah tidak bisa negatif (jika tipe 'pengeluaran', amount akan menjadi negatif di logika backend)

                // Field untuk Tipe Transaksi (Pemasukan/Pengeluaran)
                Forms\Components\Select::make('tipe')
                    ->label('Tipe Transaksi') // Label dalam bahasa Indonesia
                    ->options([
                        'penambahan' => 'Pemasukan',
                        'pengurangan' => 'Pengeluaran',
                    ])
                    ->required(), // Wajib diisi

                // Field untuk Deskripsi Transaksi
                Forms\Components\Textarea::make('deskripsi')
                    ->label('Deskripsi') // Label dalam bahasa Indonesia
                    ->maxLength(255) // Batasan panjang teks
                    ->nullable(), // Boleh kosong

                // Field untuk created_at (biasanya read-only atau disembunyikan di form Create)
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Tanggal Transaksi') // Label dalam bahasa Indonesia
                    ->default(now()) // Nilai default tanggal sekarang
                    ->disabled() // Tidak bisa diubah oleh user
                    ->hiddenOn('create') // Sembunyikan saat membuat baru karena otomatis
                    ->required(),

                // Field untuk updated_at (biasanya disembunyikan)
                Forms\Components\DateTimePicker::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->disabled()
                    ->hiddenOn('create')
                    ->hiddenOn('edit'), // Sembunyikan sepenuhnya atau hanya tampilkan di view/show (jika ada)
            ]);
    }

    /**
     * Mendefinisikan kolom-kolom untuk tampilan tabel data Transaksi.
     *
     * @param Table $table
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Kolom untuk menampilkan ID Transaksi
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Kolom untuk menampilkan Username dari relasi User
                Tables\Columns\TextColumn::make('user.username')
                    ->label('Pengguna') // Label dalam bahasa Indonesia
                    ->sortable()
                    ->searchable(),

                // Kolom untuk menampilkan Jumlah Transaksi dalam format mata uang IDR
                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah') // Label dalam bahasa Indonesia
                    ->money('IDR', true) // Format sebagai mata uang Rupiah
                    ->sortable(),

                // Kolom untuk menampilkan Tipe Transaksi
                Tables\Columns\TextColumn::make('tipe')
                    ->label('Tipe') // Label dalam bahasa Indonesia
                    ->sortable()
                    ->badge() // Menampilkan sebagai badge
                    ->color(fn (string $state): string => match ($state) { // Warna badge berdasarkan tipe
                        'penambahan' => 'success',
                        'pengurangan' => 'danger',
                        // default => 'gray',
                    }),

                // Kolom untuk menampilkan Deskripsi Transaksi
                Tables\Columns\TextColumn::make('deskripsi')
                    ->label('Deskripsi') // Label dalam bahasa Indonesia
                    ->limit(50) // Batasi panjang deskripsi di tabel
                    ->toggleable(isToggledHiddenByDefault: false), // Bisa disembunyikan secara default

                // Kolom untuk menampilkan Tanggal Transaksi dibuat
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Transaksi') // Label dalam bahasa Indonesia
                    ->dateTime() // Format sebagai tanggal dan waktu
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Bisa disembunyikan/ditampilkan

                // Kolom untuk menampilkan Waktu Terakhir Diperbarui
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui') // Label dalam bahasa Indonesia
                    ->dateTime() // Format sebagai tanggal dan waktu
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan secara default
            ])
            ->filters([
                // Filter berdasarkan Tipe Transaksi
                Tables\Filters\SelectFilter::make('tipe')
                    ->label('Filter Tipe') // Label filter
                    ->options([
                        'pemasukan' => 'Pemasukan',
                        'pengeluaran' => 'Pengeluaran',
                    ]),

                // Filter berdasarkan Pengguna
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Filter Pengguna')
                    ->relationship('user', 'username')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                // Aksi untuk mengedit data per baris
                Tables\Actions\EditAction::make(),
                // Aksi untuk menghapus data per baris
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Grup aksi massal
                Tables\Actions\BulkActionGroup::make([
                    // Aksi untuk menghapus beberapa data sekaligus
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            // Aksi yang muncul di header tabel (misalnya tombol "Buat Transaksi Baru")
            // ->headerActions([
            //     Tables\Actions\CreateAction::make(),
            // ])
            ;
    }

    /**
     * Mendefinisikan relasi yang mungkin ada untuk resource ini (jika ada).
     *
     * @return array
     */
    public static function getRelations(): array
    {
        return [
            // Contoh jika ada RelationManager lain
            // RelationManagers\SomeOtherRelationManager::class,
        ];
    }

    /**
     * Mendefinisikan halaman-halaman yang digunakan oleh resource ini.
     *
     * @return array
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransaksis::route('/'), // Halaman daftar transaksi
            // 'create' => Pages\CreateTransaksi::route('/create'), // Halaman untuk membuat transaksi baru
            // 'edit' => Pages\EditTransaksi::route('/{record}/edit'), // Halaman untuk mengedit transaksi
        ];
    }
}
