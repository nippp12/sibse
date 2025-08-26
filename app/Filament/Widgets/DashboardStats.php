<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Kas;
use App\Models\Sampah;
use App\Models\Pengepulan;
use App\Models\Penjualan;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

use Illuminate\Support\Facades\Auth;

class DashboardStats extends StatsOverviewWidget
{
    protected function getCards(): array
    {
        $user = Auth::user();

        if ($user && method_exists($user, 'hasRole') && $user->hasRole('nasabah')) {
            $saldoNasabah = $user->saldo ?? 0;
            $totalHasilPengepulanSelesai = method_exists($user, 'pengepulanPengguna') ? $user->pengepulanPengguna()
                ->where('status', 'selesai')
                ->sum('total_harga') : 0;
            $totalPenarikanSukses = method_exists($user, 'penarikan') ? $user->penarikan()
                ->where('status', 'approved')
                ->sum('jumlah') : 0;

            return [
                Card::make('Saldo Nasabah', 'Rp ' . number_format($saldoNasabah, 2, ',', '.'))
                    ->icon('heroicon-o-currency-dollar'),
                Card::make('Total Hasil Pengepulan Selesai', 'Rp ' . number_format($totalHasilPengepulanSelesai, 2, ',', '.'))
                    ->icon('heroicon-o-currency-dollar'),
                Card::make('Total Penarikan Sukses', 'Rp ' . number_format($totalPenarikanSukses, 2, ',', '.'))
                    ->icon('heroicon-o-currency-dollar'),
            ];
        }

        $totalUsers = User::count();
        $kas = Kas::first();
        $totalSaldoKas = $kas ? $kas->total_saldo : 0;

        $totalSampah = Sampah::count();
        $totalPengepulanSelesai = Pengepulan::where('status', 'selesai')->count();
        $totalHasilPengepulanSelesai = Pengepulan::where('status', 'selesai')->sum('total_harga');
        $totalPenjualanSelesai = Penjualan::where('status', 'selesai')->count();
        $totalHasilPenjualanSelesai = Penjualan::where('status', 'selesai')->sum('total_harga');
        $totalProfit = $totalHasilPenjualanSelesai - $totalHasilPengepulanSelesai;

        return [
            Card::make('Total Users', (string) $totalUsers)
                ->icon('heroicon-o-users'),
            Card::make('Saldo Kas Utama', 'Rp ' . number_format($totalSaldoKas, 2, ',', '.'))
                ->icon('heroicon-o-currency-dollar'),
            Card::make('Total Sampah', (string) $totalSampah)
                ->icon('heroicon-o-trash'),
            Card::make('Total Pengepulan Selesai', (string) $totalPengepulanSelesai)
                ->icon('heroicon-o-currency-dollar'),
            Card::make('Total Hasil Pengepulan Selesai', 'Rp ' . number_format($totalHasilPengepulanSelesai, 2, ',', '.'))
                ->icon('heroicon-o-currency-dollar'),
            Card::make('Total Penjualan Selesai', (string) $totalPenjualanSelesai)
                ->icon('heroicon-o-shopping-cart'),
            Card::make('Total Hasil Penjualan Selesai', 'Rp ' . number_format($totalHasilPenjualanSelesai, 2, ',', '.'))
                ->icon('heroicon-o-currency-dollar'),
            Card::make('Total Profit', 'Rp ' . number_format($totalProfit, 2, ',', '.'))
                ->icon('heroicon-o-currency-dollar'),
        ];
    }
}
