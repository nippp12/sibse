@section('title', 'Login')

<div>
    <section class="bg-white dark:bg-gray-900">
        <div class="h-[90vh] flex flex-col justify-center md:flex-row">
            <!-- Kolom Kiri -->
            <div class="w-full md:w-1/2 flex flex-col items-center justify-center px-2 md:px-6 py-8 mx-auto max-w-screen md lg:py-0">
                <div class="w-full bg-white border rounded-lg shadow-xl dark:border md:mt-0 sm:max-w-md xl:p-0 dark:bg-gray-800 dark:border-gray-700">
                    <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                        <h1 class="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
                            Masuk ke Akun Anda
                        </h1>
                        @if (session('error'))
                            <span class="text-red-500 text-sm">
                                {{ session('error') }}
                            </span>
                        @endif
                        <form wire:submit="login" class="space-y-4 md:space-y-6">
                            <div>
                                <x-input
                                    label="Email"
                                    placeholder="alamat email"
                                    rounded="lg"
                                    wire:model="email"
                                />
                                @if ($invalid)
                                    <p class="text-red-500 text-sm mt-2">{{ $invalid }}</p>
                                @endif
                            </div>
                            <div>
                                <x-password
                                    label="Password"
                                    placeholder="masukan password"
                                    rounded="lg"
                                    wire:model="password"
                                />
                            </div>

                            <div class="flex items-start">
                                <x-checkbox id="label" label="Remember me" wire:model="remember" value="1" />
                            </div>

                            {{-- <x-button type="submit" md primary label="Login" full rounded="lg"/> --}}
                            <button type="submit" wire:loading.attr="disabled"
                                class="w-full inline-flex justify-center whitespace-nowrap rounded-lg bg-amber-500 px-3.5 py-2.5 text-sm font-medium text-white shadow-sm shadow-amber-950/10 hover:bg-amber-600 focus:outline-none focus:ring focus:ring-amber-300 focus-visible:outline-none focus-visible:ring focus-visible:ring-amber-300 transition-colors duration-150">
                                Login
                                <x-spinner target="login"/>
                            </button>

                            <x-btn-login-google label="Login dengan Google"/>

                            <div>
                                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}" wire:navigate>
                                    {{ __('Lupa Password?') }}
                                </a>
    
                                <p class="text-sm font-light text-gray-500 dark:text-gray-400 mt-2">
                                    Belum punya akun? <a href="{{ route('register') }}" wire:navigate class="font-medium text-primary-600 hover:underline dark:text-primary-500">Daftar sekarang.</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        
            <!-- Kolom Kanan -->
            <div class="w-full md:w-1/2 flex flex-col items-center justify-center px-6 py-8 mx-auto max-w-screen md lg:py-0">
                <div class="hidden md:block">
                    <img class="w-full dark:hidden" src="{{ asset('assets/images/login.png')}}" alt="dashboard image">
                    <img class="w-full hidden dark:block" src="{{ asset('assets/images/login.png')}}" alt="dashboard image">
                </div>
            </div>
        </div>
    </section>
</div>