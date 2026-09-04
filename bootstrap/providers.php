<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\BroadcastServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\HelperServiceProvider::class,
    App\Providers\RateLimitServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    // TelescopeServiceProvider é registrado condicionalmente em AppServiceProvider::register()
    // para não quebrar em produção (composer install --no-dev exclui laravel/telescope).
];
