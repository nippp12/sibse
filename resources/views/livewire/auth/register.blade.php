<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $username = '';
    public string $email = '';
    public string $phone_number = '';
    public string $alamat = '';
    public string $no_hp = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'username' => ['required', 'string', 'max:255', 'unique:' . User::class],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'no_hp' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));

        Auth::login($user);

        $this->redirectIntended(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 px-4 py-8">
    <div class="w-full max-w-6xl bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden grid grid-cols-1 md:grid-cols-2">
        <div class="hidden md:flex flex-col items-center justify-center bg-green-600 text-white p-10">
            <h2 class="text-3xl font-bold mb-4">Buat Akun Baru</h2>
            <p class="text-sm text-green-100 text-center">
                Isi detail Anda di bawah ini untuk membuat akun baru dan mulai jelajahi aplikasi.
            </p>
            <img src="{{ asset('storage/images/enviro.png') }}"
                 alt="Ilustrasi Bank Sampah" class="mt-8 w-full max-w-sm md:max-w-md h-auto object-contain">
        </div>

        <div class="p-8">
            <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

            <!-- Session Status -->
            <x-auth-session-status class="text-center" :status="session('status')" />

            <form wire:submit="register" class="flex flex-col gap-6">
                <!-- Username -->
                <flux:input
                    wire:model="username"
                    :label="__('Username')"
                    type="text"
                    required
                    autofocus
                    autocomplete="username"
                    :placeholder="__('Username')"
                />

                <!-- Email Address -->
                <flux:input
                    wire:model="email"
                    :label="__('Email address')"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="email@example.com"
                />

                <!-- Alamat -->
                <flux:input
                    wire:model="alamat"
                    :label="__('Alamat')"
                    type="text"
                    required
                    :placeholder="__('Alamat')"
                />

                <!-- No HP -->
                <flux:input
                    wire:model="no_hp"
                    :label="__('No HP')"
                    type="text"
                    required
                    :placeholder="__('No HP')"
                />

                <!-- Password -->
                <flux:input
                    wire:model="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Password')"
                />

                <!-- Confirm Password -->
                <flux:input
                    wire:model="password_confirmation"
                    :label="__('Confirm password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Confirm password')"
                />

                <div class="flex items-center justify-end">
                    <flux:button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" variant="primary">
                        {{ __('Create account') }}
                    </flux:button>
                </div>
            </form>

            <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Already have an account?') }}
                <flux:link :href="route('login')" wire:navigate class="text-green-600 dark:text-green-400 hover:underline">{{ __('Log in') }}</flux:link>
            </div>
        </div>
    </div>
</div>
