<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Sampah; // Pastikan model ini ada dan benar
use App\Models\JenisSampah; // Pastikan model ini ada dan benar
use Livewire\WithPagination; // Opsional: jika ingin menambahkan paginasi

/**
 * Class Product
 * 
 * Kelas ini adalah blueprint (Class) untuk komponen Livewire Product.
 * Kelas ini mendefinisikan atribut dan metode yang digunakan untuk mengelola produk.
 * 
 * Konsep OOP yang digunakan:
 * - Class: Definisi kelas Product.
 * - Object: Instance kelas ini dibuat oleh Livewire saat runtime.
 * - Attribute: Properti public seperti $searchTerm, $selectedCategories, $showFilterDropdown.
 * - Method: Fungsi seperti updatedSearchTerm(), applyFilters(), render(), dll.
 * - Inheritance: Meng-extend kelas Component dari Livewire.
 * - Encapsulation: Properti public dapat diakses oleh Livewire, metode mengatur perilaku.
 * - Abstraction: Menyembunyikan detail query database di dalam metode render().
 * - Polymorphism: Metode render() override metode dari kelas induk Component.
 */
class Product extends Component
{
    // Jika menggunakan paginasi, aktifkan trait ini
    // use WithPagination;

    // Attribute: Menyimpan nilai pencarian
    public $searchTerm = '';

    // Attribute: Menyimpan kategori yang dipilih
    public $selectedCategories = [];

    // Attribute: Menyimpan status dropdown filter
    public $showFilterDropdown = false;

    // Method: Dipanggil saat $searchTerm berubah
    public function updatedSearchTerm()
    {
        // Opsional: jika Anda ingin reset paginasi saat search term berubah
        // $this->resetPage();
    }

    // Method: Dipanggil saat $selectedCategories berubah
    public function updatedSelectedCategories()
    {
        // Opsional: jika Anda ingin reset paginasi saat kategori berubah
        // $this->resetPage();
    }

    // Method: Menerapkan filter dan menutup dropdown
    public function applyFilters()
    {
        $this->showFilterDropdown = false;
    }

    // Method: Mereset filter ke nilai default
    public function resetFilters()
    {
        $this->searchTerm = '';
        $this->selectedCategories = [];
        $this->showFilterDropdown = false;
    }

    // Method: Toggle status dropdown filter
    public function toggleFilterDropdown()
    {
        $this->showFilterDropdown = !$this->showFilterDropdown;
    }

    // Method: Render view dengan data yang sudah difilter
    public function render()
    {
        $query = Sampah::query()->with(['jenis', 'satuan']); // Abstraction: menyembunyikan detail query

        if (!empty($this->searchTerm)) {
            $query->where('nama', 'like', '%' . $this->searchTerm . '%');
        }

        if (!empty($this->selectedCategories)) {
            $query->whereHas('jenis', function ($q) {
                $q->whereIn('nama', $this->selectedCategories);
            });
        }

        $sampahs = $query->orderBy('nama')->get();
        $categories = JenisSampah::orderBy('nama')->get();

        return view('livewire.product', [
            'sampahs' => $sampahs,
            'categories' => $categories,
        ]);
    }
}
