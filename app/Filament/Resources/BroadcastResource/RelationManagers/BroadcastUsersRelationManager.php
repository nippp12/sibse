<?php

namespace App\Filament\Resources\BroadcastResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\BroadcastUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Collection;
use App\Services\Fonnte;
use Filament\Notifications\Notification; // Import Notification for convenience

class BroadcastUsersRelationManager extends RelationManager
{
    protected static string $relationship = 'broadcastUsers';

    protected static ?string $title = 'Penerima';
    protected static ?string $modelLabel = 'Penerima';
    protected static ?string $pluralModelLabel = 'Penerima';

    protected ?Fonnte $fonnteService = null;

    protected function getFonnteService(): Fonnte
    {
        if (is_null($this->fonnteService)) {
            $this->fonnteService = app(Fonnte::class);
        }
        return $this->fonnteService;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->options(User::pluck('username', 'id'))
                    ->searchable()
                    ->required()
                    ->distinct()
                    ->unique(ignoreRecord: true, modifyRuleUsing: function (\Illuminate\Validation\Rules\Unique $rule, RelationManager $livewire) {
                        return $rule->where('broadcast_id', $livewire->ownerRecord->id);
                    }),
                Forms\Components\Hidden::make('status_kirim')
                    ->default('pending') // Menggunakan 'pending' sesuai migrasi
                    ->dehydrated(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.username')
            ->columns([
                Tables\Columns\TextColumn::make('user.username')
                    ->label('Nama Pengguna')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_kirim')
                    ->label('Status Kirim')
                    ->badge()
                    ->colors([
                        'pending' => 'warning', // Sesuai migrasi
                        'sukses' => 'success',  // Sesuai migrasi
                        'gagal' => 'danger',    // Sesuai migrasi
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('waktu_kirim') // Menggunakan waktu_kirim
                    ->label('Waktu Kirim')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_kirim')
                    ->options([
                        'pending' => 'Pending',
                        'sukses' => 'Sukses',
                        'gagal' => 'Gagal',
                    ])
                    ->label('Status Kirim'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Penerima Baru')
                    ->model(BroadcastUser::class)
                    ->using(function (array $data, string $model, RelationManager $livewire) {
                        $broadcastId = $livewire->ownerRecord->id;
                        $usersToAdd = collect();

                        if (isset($data['selected_recipients']) && is_array($data['selected_recipients'])) {
                            foreach ($data['selected_recipients'] as $recipientData) {
                                if (isset($recipientData['user_id'])) {
                                    $userId = $recipientData['user_id'];
                                    $exists = BroadcastUser::where('broadcast_id', $broadcastId)
                                                          ->where('user_id', $userId)
                                                          ->exists();
                                    if (!$exists) {
                                        $usersToAdd->push([
                                            'broadcast_id' => $broadcastId,
                                            'user_id' => $userId,
                                            'status_kirim' => 'pending', // Menggunakan 'pending'
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);
                                    }
                                }
                            }
                        }

                        if ($usersToAdd->isNotEmpty()) {
                            BroadcastUser::insert($usersToAdd->toArray());
                            return BroadcastUser::where('broadcast_id', $broadcastId)
                                                ->where('user_id', $usersToAdd->first()['user_id'])
                                                ->first();
                        }
                        return null;
                    })
                    ->successNotificationTitle('Penerima berhasil ditambahkan!')
                    ->failureNotificationTitle('Gagal menambahkan penerima. Mungkin ada duplikasi atau tidak ada user dipilih.')
                    ->form([
                        Forms\Components\Select::make('role_selector')
                            ->label('Tambah Berdasarkan Role')
                            ->multiple()
                            ->options(Role::pluck('name', 'name'))
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $selectedRoles = (array) $state;
                                $existingRecipientsInRepeater = collect($get('selected_recipients') ?? []);

                                if (!empty($selectedRoles)) {
                                    $usersToAdd = User::query()
                                        ->whereHas('roles', fn ($q) => $q->whereIn('name', $selectedRoles))
                                        ->get();

                                    foreach ($usersToAdd as $user) {
                                        if (!$existingRecipientsInRepeater->contains('user_id', $user->id)) {
                                            $existingRecipientsInRepeater->push([
                                                'user_id' => $user->id,
                                                'status_kirim' => 'pending', // Menggunakan 'pending'
                                            ]);
                                        }
                                    }
                                }
                                $set('selected_recipients', $existingRecipientsInRepeater->toArray());
                            })
                            ->dehydrated(false)
                            ->helperText('Pilih role untuk menambahkan semua user dengan role tersebut ke daftar di bawah.'),

                        Forms\Components\Repeater::make('selected_recipients')
                            ->label('Daftar Penerima yang Akan Ditambahkan')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('User')
                                    ->options(User::pluck('username', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->distinct()
                                    ->unique(
                                        ignoreRecord: true,
                                        modifyRuleUsing: function (\Illuminate\Validation\Rules\Unique $rule, Get $get, RelationManager $livewire) {
                                            $broadcastId = $livewire->ownerRecord->id;
                                            return $rule->where('broadcast_id', $broadcastId);
                                        }
                                    ),
                                Forms\Components\Hidden::make('status_kirim')
                                    ->default('pending') // Menggunakan 'pending'
                                    ->dehydrated(true),
                            ])
                            ->itemLabel(fn (array $state): ?string => isset($state['user_id']) ? User::find($state['user_id'])?->username : 'Pilih User')
                            ->columns(1)
                            ->minItems(1)
                            ->helperText('Tambahkan user secara manual atau gunakan pilihan role di atas. Setiap user harus unik.')
                            ->default([])
                            ->reorderable(false)
                            ->collapsible()
                            ->cloneable(),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->options(User::pluck('username', 'id'))
                            ->disabled(),
                        Forms\Components\Select::make('status_kirim')
                            ->options([
                                'pending' => 'Pending',
                                'sukses' => 'Sukses',
                                'gagal' => 'Gagal',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('waktu_kirim') // Menggunakan waktu_kirim
                            ->label('Waktu Kirim')
                            ->nullable(),
                        Forms\Components\Textarea::make('deskripsi') // Menambahkan field deskripsi
                            ->label('Deskripsi Respon API')
                            ->rows(3)
                            ->readOnly() // Biasanya read-only di form edit
                            ->nullable(),
                        Forms\Components\TextInput::make('message_id') // Menambahkan field message_id
                            ->label('ID Pesan Fonnte')
                            ->readOnly() // Biasanya read-only di form edit
                            ->nullable(),
                    ]),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('send_message_now')
                    ->label('Kirim Pesan')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function (BroadcastUser $broadcastUser) {
                        $broadcastUser->load('user', 'broadcast');

                        if (!$broadcastUser->user || !$broadcastUser->user->no_hp) {
                            Notification::make()
                                ->title('Gagal mengirim: Nomor HP user tidak ditemukan.')
                                ->danger()
                                ->send();
                            // Optional: Update status broadcastUser to 'gagal' with description for this specific case
                            $broadcastUser->update([
                                'status_kirim' => 'gagal',
                                'deskripsi' => 'Nomor HP user tidak ditemukan atau kosong.',
                                'waktu_kirim' => now(), // Catat waktu gagal
                            ]);
                            return;
                        }

                        $phoneNumber = $broadcastUser->user->no_hp;
                        $messageContent = $this->getFonnteService()->prepareMessage($broadcastUser->broadcast, $broadcastUser->user);

                        $result = $this->getFonnteService()->sendSingleMessage($phoneNumber, $messageContent);

                        if ($result['status']) {
                            $broadcastUser->update([
                                'status_kirim' => 'sukses', // Menggunakan 'sukses'
                                'waktu_kirim' => now(),      // Menggunakan waktu_kirim
                                'message_id' => $result['message_id'] ?? null,
                                'deskripsi' => $result['message'] ?? 'Pesan berhasil dikirim ke Fonnte.', // Simpan deskripsi sukses
                            ]);
                            Notification::make()
                                ->title('Pesan berhasil dikirim ke Fonnte!')
                                ->success()
                                ->send();
                        } else {
                            $broadcastUser->update([
                                'status_kirim' => 'gagal',    // Menggunakan 'gagal'
                                'deskripsi' => $result['message'] ?? 'Gagal mengirim pesan.', // Simpan deskripsi gagal
                                'waktu_kirim' => now(), // Catat waktu gagal
                            ]);
                            Notification::make()
                                ->title('Gagal mengirim pesan: ' . $result['message'])
                                ->danger()
                                ->send();
                            \Illuminate\Support\Facades\Log::error('Fonnte sendSingleMessage failed for BroadcastUser ' . $broadcastUser->id . ': ' . $result['message']);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('send_selected_messages_now')
                        ->label('Kirim Sekarang')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $messagesToSend = [];
                            $failedBroadcastUserIds = [];

                            $records->load('user', 'broadcast');

                            foreach ($records as $broadcastUser) {
                                if ($broadcastUser->status_kirim !== 'sukses' && // Hanya proses yang belum 'sukses'
                                    $broadcastUser->user &&
                                    $broadcastUser->user->no_hp
                                ) {
                                    $phoneNumber = $this->getFonnteService()->formatPhoneNumber($broadcastUser->user->no_hp);
                                    if (!empty($phoneNumber)) {
                                        $messagesToSend[] = [
                                            'broadcast_user_id' => $broadcastUser->id,
                                            'target' => $phoneNumber,
                                            'message' => $this->getFonnteService()->prepareMessage($broadcastUser->broadcast, $broadcastUser->user),
                                        ];
                                    } else {
                                        $failedBroadcastUserIds[] = [
                                            'id' => $broadcastUser->id,
                                            'reason' => 'Nomor HP user tidak valid setelah diformat.',
                                        ];
                                        \Illuminate\Support\Facades\Log::warning('Fonnte bulk send: Invalid formatted phone number for BroadcastUser ID: ' . $broadcastUser->id);
                                    }
                                } else {
                                    // Jika status sudah sukses atau tidak ada no_hp
                                    $failedBroadcastUserIds[] = [
                                        'id' => $broadcastUser->id,
                                        'reason' => 'Status sudah sukses atau nomor HP tidak ditemukan.',
                                    ];
                                }
                            }

                            if (empty($messagesToSend)) {
                                Notification::make()
                                    ->title('Tidak ada pesan yang valid untuk dikirim.')
                                    ->warning()
                                    ->send();

                                // Update status gagal untuk yang tidak valid di awal
                                if (!empty($failedBroadcastUserIds)) {
                                    $ids = collect($failedBroadcastUserIds)->pluck('id')->toArray();
                                    foreach ($failedBroadcastUserIds as $failedItem) {
                                        BroadcastUser::find($failedItem['id'])->update([
                                            'status_kirim' => 'gagal',
                                            'deskripsi' => $failedItem['reason'],
                                            'waktu_kirim' => now(),
                                        ]);
                                    }
                                    Notification::make()
                                        ->title('Beberapa pesan gagal dijadwalkan karena masalah data.')
                                        ->warning()
                                        ->send();
                                }
                                return;
                            }

                            $result = $this->getFonnteService()->sendBulkMessages($messagesToSend);
                            $now = now();

                            if ($result['status']) {
                                Notification::make()
                                    ->title('Pesan terpilih berhasil dikirim ke Fonnte!')
                                    ->success()
                                    ->send();

                                // Fonnte bulk send detail bisa bervariasi.
                                // Idealnya, kita cocokkan message_id dari Fonnte dengan broadcast_user_id.
                                // Untuk saat ini, asumsikan jika bulk call sukses, semua yang dikirim ke Fonnte dianggap 'sukses'.
                                // Jika Fonnte mengembalikan array detail per pesan (misal, $result['data'] berisi array of objects dengan 'id' dan 'status'),
                                // Anda bisa loop di sana untuk update yang lebih granular.
                                // Untuk sementara, kita update semua yang masuk ke $messagesToSend sebagai 'sukses'.

                                $successfullySentIds = collect($messagesToSend)->pluck('broadcast_user_id')->toArray();
                                BroadcastUser::whereIn('id', $successfullySentIds)
                                             ->update([
                                                 'status_kirim' => 'sukses',      // Menggunakan 'sukses'
                                                 'waktu_kirim' => $now,           // Menggunakan waktu_kirim
                                                 'deskripsi' => $result['message'] ?? 'Pesan massal berhasil dikirim ke Fonnte.', // Simpan deskripsi sukses
                                             ]);

                                // Jika Fonnte mengembalikan ID individu dalam bulk response (misal: $result['data'] adalah array of objects),
                                // Anda bisa memperbarui 'message_id' secara spesifik di sini.
                                // Contoh (jika $result['data'] adalah array seperti Fonnte docs):
                                // if (isset($result['data']) && is_array($result['data'])) {
                                //     foreach ($result['data'] as $fonnteItem) {
                                //         if (isset($fonnteItem['id']) && isset($fonnteItem['target'])) {
                                //             // Cari broadcast user berdasarkan target atau cara lain
                                //             // Ini lebih kompleks karena 'target' dari Fonnte perlu dicocokkan kembali ke broadcast_user_id
                                //             // Mungkin butuh mekanisme antrian atau webhook lanjutan untuk ini
                                //         }
                                //     }
                                // }

                            } else {
                                Notification::make()
                                    ->title('Gagal mengirim pesan massal: ' . $result['message'])
                                    ->danger()
                                    ->send();
                                $failedInApiIds = collect($messagesToSend)->pluck('broadcast_user_id')->toArray();
                                BroadcastUser::whereIn('id', $failedInApiIds)
                                             ->update([
                                                 'status_kirim' => 'gagal',    // Menggunakan 'gagal'
                                                 'deskripsi' => $result['message'] ?? 'Gagal mengirim pesan massal ke Fonnte.', // Simpan deskripsi gagal
                                                 'waktu_kirim' => $now, // Catat waktu gagal
                                             ]);
                                \Illuminate\Support\Facades\Log::error('Fonnte sendBulkMessages failed: ' . $result['message']);
                            }

                            // Update status untuk record yang gagal di awal (misal: no_hp tidak valid)
                            if (!empty($failedBroadcastUserIds)) {
                                foreach ($failedBroadcastUserIds as $failedItem) {
                                    // Pastikan kita tidak menimpa status 'sukses' jika sudah berhasil di Fonnte API
                                    // Misalnya, jika ada yang 'pending' atau 'gagal' sebelumnya, kita bisa update.
                                    $bu = BroadcastUser::find($failedItem['id']);
                                    if ($bu && $bu->status_kirim !== 'sukses') {
                                        $bu->update([
                                            'status_kirim' => 'gagal',
                                            'deskripsi' => $failedItem['reason'],
                                            'waktu_kirim' => now(),
                                        ]);
                                    }
                                }
                                Notification::make()
                                    ->title('Beberapa pesan gagal dikirim karena masalah data awal.')
                                    ->warning()
                                    ->send();
                            }

                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}