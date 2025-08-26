<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink($this->only('email'));

        session()->flash('status', __('A reset link will be sent if the account exists.'));
    }
}; ?>

<div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 px-4 py-8">
    <div class="w-full max-w-6xl bg-white dark:bg-zinc-800 shadow-lg rounded-lg overflow-hidden grid grid-cols-1 md:grid-cols-2">
        <div class="hidden md:flex flex-col items-center justify-center bg-green-600 text-white p-10">
            <h2 class="text-3xl font-bold mb-4">{{ __('Forgot password') }}</h2>
            <p class="text-sm text-green-100 text-center">
                {{ __('Enter your email to receive a password reset link') }}
            </p>
            <img src="{{ asset('storage/images/enviro.png') }}"
                 alt="Ilustrasi Bank Sampah" class="mt-8 w-full max-w-sm md:max-w-md h-auto object-contain">
        </div>

        <div class="p-8">
            <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

            <!-- Session Status -->
            <x-auth-session-status class="text-center mb-4" :status="session('status')" />

            <form wire:submit="sendPasswordResetLink" class="flex flex-col gap-6">
                <!-- Email Address -->
                <flux:input
                    wire:model="email"
                    :label="__('Email Address')"
                    type="email"
                    required
                    autofocus
                    placeholder="email@example.com"
                />

                <flux:button variant="primary" type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    {{ __('Email password reset link') }}
                </flux:button>
            </form>

            <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400 mt-4">
                {{ __('Or, return to') }}
                <flux:link :href="route('login')" wire:navigate class="text-green-600 dark:text-green-400 hover:underline">
                    {{ __('log in') }}
                </flux:link>
            </div>
        </div>
    </div>
</div>
