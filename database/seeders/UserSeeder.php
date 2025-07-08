<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role; // Import model Role dari Spatie

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Menonaktifkan pemeriksaan foreign key sementara untuk operasi truncate
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // Mengosongkan tabel users. Perlu diingat ini akan menghapus semua user yang ada.
        User::truncate();
        // Mengaktifkan kembali pemeriksaan foreign key
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // --- Pastikan Roles Tersedia ---
        // Penting: Pastikan roles ini dibuat sebelum user agar role_id dapat diisi.
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // --- Membuat User Admin dan Menetapkan Peran ---
        $adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'alamat' => 'Jl. Admin No.1',
            'no_hp' => '081234567890',
            'saldo' => 1000000.00,
            'role_id' => $superAdminRole->id, // Menambahkan role_id di sini
        ]);
        // Tetap menetapkan peran melalui Spatie's assignRole() untuk konsistensi,
        // meskipun role_id sudah disimpan langsung di tabel users.
        $adminUser->assignRole($superAdminRole);


        // --- Membuat User Biasa dan Menetapkan Peran ---
        $user1 = User::create([
            'username' => 'user1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password123'),
            'alamat' => 'Jl. User No.1',
            'no_hp' => '081234567891',
            'saldo' => 500000.00,
            'role_id' => $userRole->id, // Menambahkan role_id di sini
        ]);
        $user1->assignRole($userRole);

        $user2 = User::create([
            'username' => 'user2',
            'email' => 'user2@example.com',
            'password' => Hash::make('password123'),
            'alamat' => 'Jl. User No.2',
            'no_hp' => '081234567892',
            'saldo' => 750000.00,
            'role_id' => $userRole->id, // Menambahkan role_id di sini
        ]);
        $user2->assignRole($userRole);

        $this->command->info('Users and roles seeded successfully!');
    }
}
