<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/dashboard'; // Ajuste aqui seu redirecionamento

    public function boot(): void
    {
        parent::boot();
    }
}