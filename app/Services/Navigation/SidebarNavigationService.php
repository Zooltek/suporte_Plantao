<?php

namespace App\Services\Navigation;

use App\Models\User;
use App\Services\Access\AccessService;

class SidebarNavigationService
{
    public function __construct(
        private readonly AccessService $accessService,
    ) {}

    /**
     * @return array<int, array{label: string, route: string, module: string, hoverBg: string, hoverText: string, hoverBorder: string, iconSvg: string}>
     */
    public function getNavigationItems(User $user, string $currentModule): array
    {
        $items = [];

        if ($currentModule !== 'admin' && $this->accessService->isAdmin($user)) {
            $items[] = $this->buildItem('Painel Admin', 'admin.dashboard', 'admin');
        }

        if ($currentModule !== 'agent' && $this->accessService->hasStaffAccess($user)) {
            $items[] = $this->buildItem('Painel Suporte', 'agent.index', 'agent');
        }

        if ($currentModule !== 'tickets' && $this->accessService->hasStaffAccess($user)) {
            $items[] = $this->buildItem('Painel Tickets', 'agent.ticket.index', 'tickets');
        }

        if ($currentModule !== 'crm' && $this->accessService->canAccessFeedback($user)) {
            $items[] = $this->buildItem('Painel CRM', 'crm.index', 'crm');
        }

        if ($currentModule !== 'tasks' && $this->accessService->hasStaffAccess($user)) {
            $items[] = $this->buildItem('Minhas Tarefas', 'tasks.index', 'tasks');
        }

        if ($currentModule !== 'knowledge' && $this->accessService->isAdmin($user)) {
            $items[] = $this->buildItem('EasyWiki', 'agent.knowledge.index', 'knowledge');
        }

        return $items;
    }

    /**
     * @return array{label: string, route: string, module: string, hoverBg: string, hoverText: string, hoverBorder: string, iconSvg: string}
     */
    private function buildItem(string $label, string $route, string $module): array
    {
        $styles = $this->getModuleStyles($module);

        return [
            'label' => $label,
            'route' => $route,
            'module' => $module,
            'hoverBg' => $styles['hoverBg'],
            'hoverText' => $styles['hoverText'],
            'hoverBorder' => $styles['hoverBorder'],
            'iconSvg' => $styles['iconSvg'],
        ];
    }

    /**
     * @return array{hoverBg: string, hoverText: string, hoverBorder: string, iconSvg: string}
     */
    private function getModuleStyles(string $module): array
    {
        return match ($module) {
            'admin' => [
                'hoverBg' => 'hover:bg-indigo-50',
                'hoverText' => 'hover:text-indigo-700',
                'hoverBorder' => 'hover:border-indigo-300',
                'iconSvg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
            ],
            'agent' => [
                'hoverBg' => 'hover:bg-orange-50',
                'hoverText' => 'hover:text-orange-700',
                'hoverBorder' => 'hover:border-orange-300',
                'iconSvg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>',
            ],
            'tickets' => [
                'hoverBg' => 'hover:bg-rose-50',
                'hoverText' => 'hover:text-rose-700',
                'hoverBorder' => 'hover:border-rose-300',
                'iconSvg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>',
            ],
            'crm' => [
                'hoverBg' => 'hover:bg-teal-50',
                'hoverText' => 'hover:text-teal-700',
                'hoverBorder' => 'hover:border-teal-300',
                'iconSvg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
            ],
            'tasks' => [
                'hoverBg' => 'hover:bg-purple-50',
                'hoverText' => 'hover:text-purple-700',
                'hoverBorder' => 'hover:border-purple-300',
                'iconSvg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
            ],
            'knowledge' => [
                'hoverBg' => 'hover:bg-emerald-50',
                'hoverText' => 'hover:text-emerald-700',
                'hoverBorder' => 'hover:border-emerald-300',
                'iconSvg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
            ],
            default => [
                'hoverBg' => 'hover:bg-gray-50',
                'hoverText' => 'hover:text-gray-700',
                'hoverBorder' => 'hover:border-gray-300',
                'iconSvg' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
            ],
        };
    }
}
