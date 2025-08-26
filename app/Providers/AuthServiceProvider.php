<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Policies\UserPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */


    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Bisa tambahkan Gate tambahan di sini jika perlu
    }
}
