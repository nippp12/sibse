<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle an incoming authentication request.
     */
     public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        // Ubah redirect ke path Filament yang sekarang jadi /dashboard
        $this->redirectIntended(default: '/', navigate: true);
    }


    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>
<div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 px-4 py-8">
    <div class="w-full max-w-6xl bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden grid grid-cols-1 md:grid-cols-2">
        <div class="hidden md:flex flex-col items-center justify-center bg-green-600 text-white p-10">
            <h2 class="text-3xl font-bold mb-4">Selamat Datang Kembali!</h2>
            <p class="text-sm text-green-100 text-center">
                Masukkan kredensial Anda untuk mengakses akun dan jelajahi lebih lanjut.
            </p>
            {{-- Mengganti ilustrasi default dengan ilustrasi dari desain Anda --}}
            <img src="{{ asset('storage/images/enviro.png') }}"
                 alt="Ilustrasi Bank Sampah" class="mt-8 w-full max-w-sm md:max-w-md h-auto object-contain">
        </div>

        <div class="p-8">
            <x-auth-header :title="__('Masuk ke akun Anda')" :description="__('Masukkan email dan kata sandi Anda di bawah untuk masuk')" />
            
            <x-auth-session-status class="text-center mb-4" :status="session('status')" />

            <form wire:submit="login" class="flex flex-col gap-6">
                <flux:input
                    wire:model="email"
                    :label="__('Alamat Email')"
                    type="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@example.com"
                />

                <div class="relative">
                    <flux:input
                        wire:model="password"
                        :label="__('Kata Sandi')"
                        type="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />

                    @if (Route::has('password.request'))
                        <flux:link class="absolute right-0 top-0 text-sm text-green-600 dark:text-green-400 hover:underline" :href="route('password.request')" wire:navigate>
                            {{ __('Lupa kata sandi Anda?') }}
                        </flux:link>
                    @endif
                </div>

                <flux:checkbox wire:model="remember" :label="__('Ingat saya')" />

                <div>
                    {{-- TOMBOL MASUK DIBUAT HIJAU --}}
                    <flux:button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" variant="primary">
                        {{ __('Masuk') }}
                    </flux:button>
                </div>
            </form>

            @if (Route::has('register'))
                <div class="mt-4 space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __("Belum punya akun?") }}
                    <flux:link :href="route('register')" wire:navigate class="text-green-600 dark:text-green-400 hover:underline">
                        {{ __('Daftar') }}
                    </flux:link>
                </div>
            @endif
        </div>
    </div>
</div>