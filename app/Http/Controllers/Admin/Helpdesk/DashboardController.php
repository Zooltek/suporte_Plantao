<?php

namespace App\Http\Controllers\Admin\Helpdesk;

use App\Http\Controllers\Controller;
use App\Services\Admin\Helpdesk\HelpdeskService;
use Illuminate\View\View;

/**
 * Painel de administração do Helpdesk.
 * Controller magro — delega toda lógica à HelpdeskService (SRP).
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly HelpdeskService $service
    ) {}

    public function index(): View
    {
        $metrics = $this->service->getDashboardMetrics();

        return view('admin.helpdesk.dashboard', $metrics);
    }
}
