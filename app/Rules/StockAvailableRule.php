<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Sampah;

class StockAvailableRule implements Rule
{
    protected $sampahId;

    public function __construct($sampahId)
    {
        $this->sampahId = $sampahId;
    }

    public function passes($attribute, $value)
    {
        if (!$this->sampahId) {
            return true; // No sampah selected, skip validation
        }

        // Consider pending transactions or reserved stock here if applicable
        // For now, just get current stock
        $stock = Sampah::find($this->sampahId)?->stock ?? 0;

        return $value <= $stock;
    }

    public function message()
    {
        $stock = Sampah::find($this->sampahId)?->stock ?? 0;
        return "Kuantitas tidak boleh melebihi stok yang tersedia ({$stock}) untuk item ini.";
    }
}
