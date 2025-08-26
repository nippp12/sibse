<div class="min-h-screen bg-white dark:bg-gray-800 font-sans text-gray-900 dark:text-gray-100">

    <section class="bg-white dark:bg-gray-800 py-10">
        <div class="max-w-screen-xl px-4 mx-auto w-full">
            <div
                class="sticky top-20 z-10 bg-white dark:bg-gray-700 rounded-lg shadow p-4 flex flex-col md:flex-row justify-between items-center gap-4 mt-4 border border-gray-200 dark:border-gray-600"
            >
                <div class="w-full md:w-1/2">
                    <label for="search" class="sr-only">Cari Jenis Sampah</label>
                    <div class="relative">
                        <div
                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                        >
                            <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                        </div>
                        <input
                            type="text"
                            id="search"
                            placeholder="Cari jenis sampah..."
                            class="block w-full pl-10 p-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                            wire:model.live="searchTerm" {{-- PENTING: Menggunakan .live untuk real-time Livewire 3 --}}
                        />
                    </div>
                </div>
                <div class="relative">
                    <button
                        id="filterButton"
                        class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition ease-in-out duration-200"
                        type="button"
                        wire:click="toggleFilterDropdown"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 08 17v-5.586L3.293 6.707A1 1 0 03 6V3z"
                                clip-rule="evenodd"
                            ></path>
                        </svg>
                        Filter Kategori
                    </button>
                    <div
                        id="filterDropdown"
                        class="absolute right-0 mt-2 w-48 p-3 bg-white dark:bg-gray-700 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600"
                        style="{{ $showFilterDropdown ? 'display: block;' : 'display: none;' }}"
                    >
                        <h6 class="text-sm font-medium mb-2 text-gray-900 dark:text-white">Jenis Sampah</h6>
                        <ul class="space-y-2">
                            @foreach ($categories as $category)
                            <li>
                                <label class="inline-flex items-center">
                                    <input
                                        type="checkbox"
                                        class="form-checkbox text-green-500 rounded focus:ring-green-500"
                                        wire:model="selectedCategories"
                                        value="{{ $category->nama }}"
                                    />
                                    <span class="ml-2 text-sm dark:text-white">{{ $category->nama }}</span>
                                </label>
                            </li>
                            @endforeach
                        </ul>
                        <div class="mt-3 flex flex-col gap-2">
                            <button
                                class="w-full text-sm py-2 bg-gray-200 dark:bg-gray-800 text-gray-800 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-900 transition ease-in-out duration-200"
                                wire:click="resetFilters"
                            >
                                Reset Filter
                            </button>
                            <button
                                class="w-full text-sm py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition ease-in-out duration-200"
                                wire:click="applyFilters"
                            >
                                Terapkan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 mt-8">
                @foreach ($sampahs as $sampah)
                <div wire:key="{{ $sampah->id }}" class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-sm p-4 flex flex-col items-center text-center">
                    <div class="relative w-full aspect-square mb-3 bg-gray-100 dark:bg-gray-600 flex items-center justify-center rounded-lg overflow-hidden">
                        @if ($sampah->image)
                        <img src="{{ asset('storage/' . $sampah->image) }}" alt="{{ $sampah->nama }}" class="object-cover w-full h-full" />
                        @else
                        <img src="https://via.placeholder.com/150x150?text={{ urlencode($sampah->nama) }}" alt="{{ $sampah->nama }}" class="object-cover w-full h-full" />
                        @endif
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $sampah->nama }}</h3>
                    <p class="text-green-500 font-bold text-base mt-1">Rp {{ number_format($sampah->harga, 0, ',', '.') }}/{{ $sampah->satuan->nama ?? '' }}</p>
                    <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">Kategori: {{ $sampah->jenis->nama ?? '' }}</p>
                </div>
                @endforeach
                {{-- Tampilkan pesan jika tidak ada sampah yang ditemukan --}}
                @if($sampahs->isEmpty())
                    <p class="col-span-full text-center text-gray-500 dark:text-gray-400">Tidak ada jenis sampah yang ditemukan.</p>
                @endif
            </div>

            <div class="flex justify-center mt-10">
                <button class="text-white bg-green-500 hover:bg-green-600 px-6 py-3 rounded-lg text-base font-medium transition ease-in-out duration-200">
                    Muat Lebih Banyak Jenis Sampah
                </button>
            </div>
        </div>
    </section>

</div>