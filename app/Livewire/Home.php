<?php

namespace App\Livewire;

use App\Models\LandingPage;
use Livewire\Component;
use App\Models\ProdukJual;
use App\Models\ProdukKategori;

use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;

class Home extends Component
{public function render()
    {
        return view('livewire.home');
    }
}