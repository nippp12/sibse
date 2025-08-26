<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use Livewire\Volt\Volt;
use App\Livewire\Home;
use App\Livewire\About;
use App\Livewire\Contact;
use App\Livewire\Product;

Route::get('/', Home::class)->name('home');
Route::get('/product', Product::class)->name('product');
// Route::get('product/{slug}', Detail::class)->name('detail');
Route::get('/about', About::class)->name('about');
Route::get('/contact', Contact::class)->name('contact');

// Route::view('dashboard', 'dashboard')
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Route::post('/dashboard/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
})->name('filament.admin.auth.logout'); 

require __DIR__.'/auth.php';
