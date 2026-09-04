<?php

namespace App\View\Composers;

use App\Services\Navigation\SidebarNavigationService;
use Illuminate\View\View;

class TasksSidebarComposer
{
    public function __construct(
        private readonly SidebarNavigationService $navigationService,
    ) {}

    public function compose(View $view): void
    {
        $user = auth('admin')->user();

        if ($user === null) {
            $view->with('navigationItems', []);
            return;
        }

        $view->with(
            'navigationItems',
            $this->navigationService->getNavigationItems($user, 'tasks'),
        );

        $view->with('navigationService', $this->navigationService);
    }
}
