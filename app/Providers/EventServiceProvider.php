<?php

namespace App\Providers;

use App\Events\SomeEvent;
use App\Listeners\EventListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Event to listener mappings.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        SomeEvent::class => [
            EventListener::class,
        ],
    ];
}
