<nav class="bg-white dark:bg-gray-800 antialiased fixed w-full z-50 shadow" role="navigation">
    <div class="max-w-screen-xl mx-auto px-4 2xl:px-0 py-4">
        <div class="flex items-center justify-between">

            {{-- Logo --}}
            <div class="flex items-center space-x-8">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-3">
                    {{-- <div class="w-auto h-8 lg:h-12 xl:h-12 2xl:h-16 bg-gray-300 dark:bg-gray-700 flex items-center justify-center rounded">
                        <span class="text-gray-600 dark:text-gray-400 text-sm font-medium">Logo</span>
                    </div> --}}
                    <div class="flex flex-col">
                        <span class="font-bold text-green-500 text-base sm:text-lg xl:text-2xl 2xl:text-4xl">
                            {{ config('app.name', ) }}
                        </span>
                        <span class="text-[0.5rem] md:text-[0.8rem] lg:text-xs xl:text-sm 2xl:text-[1.2rem] leading-tight">
                            Sistem Informasi Bank Sampah Enviro
                        </span>
                    </div>
                </a>
            </div>

            {{-- Menu Desktop --}}
            <ul class="hidden lg:flex items-center gap-6 md:gap-8 py-3">
                @php $route = Route::currentRouteName(); @endphp
                @foreach([
                    'home' => 'Beranda',
                    'about' => 'Tentang Kami',
                    'product' => 'Produk',
                    'contact' => 'Kontak',
                ] as $r => $label)
                    <li>
                        <a href="{{ route($r) }}" wire:navigate
                           class="flex font-medium text-base lg:text-base 2xl:text-lg 
                                 text-gray-900 hover:text-primary-700 dark:text-white dark:hover:text-primary-500 
                                 {{ $route === $r ? 'border-b-2 border-green-500' : '' }}">
                            {{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- Right: Cart + Account + Mobile --}}
            <div x-data="{ open: false, miniMenu: false, miniAccount: false }" class="flex items-center lg:space-x-2">

                {{-- Cart --}}
                {{-- @persist('cart')
                <div class="relative">
                    <a href="{{ route('cart') }}" wire:navigate
                       class="inline-flex items-center justify-center p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-900 dark:text-white">
                        <svg class="w-5 h-5 lg:me-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 4h1.5L9 16h8M9 16a2 2 0 1 0 0 4 2 2 0 0 0 0-4m8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-8.5-3h9.25L19 7H7.312"/>
                        </svg>
                        <span class="hidden sm:flex">My Cart</span>
                    </a>
                    <span class="absolute -top-1 -left-1 px-2 rounded-full bg-green-500 text-white text-xs">0</span>
                </div>
                @endpersist --}}

                {{-- Account Dropdown --}}
                <div class="hidden md:block relative">
                    <button @click="open = !open" aria-expanded="false"
                            class="inline-flex items-center p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-900 dark:text-white">
                        <svg class="w-5 h-5 me-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-width="2" d="M7 17v1a1 1 0 001 1h8a1 1 0 001-1v-1a3 3 0 00-3-3h-4a3 3 0 00-3 3zM15 8a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        @auth
                            {{ auth()->user()->name }}
                        @else
                            Login/Daftar
                        @endauth
                        <svg class="w-4 h-4 ms-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/>
                        </svg>
                    </button>

                    {{-- Dropdown --}}
                    <div x-show="open" @click.away="open = false"
                         class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-700 rounded-lg shadow z-50 overflow-hidden divide-y divide-gray-100 dark:divide-gray-600"
                         x-cloak x-collapse>
                        @auth
                            <ul class="p-2 text-sm text-gray-900 dark:text-white font-medium">
                                @if(auth()->user()->getRoleNames()->first() !== 'user')
                                    <li><a href="{{ url('/dashboard') }}" class="block px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">Dashboard</a></li>
                                @endif
                                {{-- <li>
                                    <a href="{{ route('settings.profile') }}" wire:navigate title=""
                                        class="inline-flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600">
                                        Akun Saya
                                    </a>
                                </li> --}}
                            </ul>
                            <div class="p-2 text-sm text-gray-900 dark:text-white">
                                <button onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                        class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
                                    Sign Out
                                </button>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
                            </div>
                        @else
                            <ul class="p-2 text-sm text-gray-900 dark:text-white font-medium">
                                <li><a href="{{ route('login') }}" wire:navigate class="block px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">Login</a></li>
                                <li><a href="{{ route('register') }}" wire:navigate class="block px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">Register</a></li>
                            </ul>
                        @endauth
                    </div>
                </div>

                {{-- Mobile Button --}}
                <div class="lg:hidden">
                    <button @click="miniMenu = !miniMenu" aria-expanded="false"
                            class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h14"/>
                        </svg>
                    </button>

                    {{-- Mobile Dropdown --}}
                    <div x-show="miniMenu" @click.away="miniMenu = false" class="absolute left-4 right-4 mt-4 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg py-3 px-4 z-30" x-cloak x-collapse>
                        <ul class="space-y-3 text-sm font-medium text-gray-900 dark:text-white">
                            @auth
                                @if(auth()->user()->getRoleNames()->first() !== 'user')
                                    <li><a href="{{ url('/dashboard') }}" class="block hover:text-primary-700 dark:hover:text-primary-500">Dashboard</a></li>
                                @endif
                                {{-- <li><a href="{{ route('account') }}" wire:navigate class="block hover:text-primary-700 dark:hover:text-primary-500">Akun Saya</a></li> --}}
                                <li><button onclick="event.preventDefault(); document.getElementById('logout-form-mobile').submit();" class="w-full text-left hover:text-primary-700 dark:hover:text-primary-500">Sign Out</button></li>
                                <form id="logout-form-mobile" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
                            @else
                                <li><a href="{{ route('login') }}" wire:navigate class="block hover:text-primary-700 dark:hover:text-primary-500">Login</a></li>
                                <li><a href="{{ route('register') }}" wire:navigate class="block hover:text-primary-700 dark:hover:text-primary-500">Register</a></li>
                            @endauth

                            <li><a href="{{ route('home') }}" wire:navigate class="block hover:text-primary-700 dark:hover:text-primary-500">Beranda</a></li>
                            <li><a href="{{ route('about') }}" wire:navigate class="block hover:text-primary-700 dark:hover:text-primary-500">Tentang Kami</a></li>
                            <li><a href="{{ route('product') }}" wire:navigate class="block hover:text-primary-700 dark:hover:text-primary-500">Produk</a></li>
                            <li><a href="{{ route('contact') }}" wire:navigate class="block hover:text-primary-700 dark:hover:text-primary-500">Kontak</a></li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>
</nav>