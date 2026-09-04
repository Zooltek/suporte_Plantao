<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Knowledge\KnowledgeArticle;
use App\Models\Schedule;
use App\Models\Schedule\Record;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Policies\KnowledgeArticlePolicy;
use App\Policies\RecordPolicy;
use App\Policies\SchedulePolicy;
use App\Policies\TicketPolicy;
use App\Services\Access\AccessService;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Ticket::class          => TicketPolicy::class,
        Schedule::class        => SchedulePolicy::class,
        Record::class          => RecordPolicy::class,
        KnowledgeArticle::class => KnowledgeArticlePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Define the 'access-admin' gate, which checks if a user is an admin.
        // This replaces the old 'admin' middleware and centralizes authorization logic.
        Gate::define('access-admin', function (User $user) {
            return app(AccessService::class)->isAdmin($user);
        });

        // Define the 'access-agent' gate, which checks if a user is an agent.
        Gate::define('access-agent', function (User $user) {
            return app(AccessService::class)->isAgent($user);
        });
    }
}
