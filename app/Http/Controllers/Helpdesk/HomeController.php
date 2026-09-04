<?php

namespace App\Http\Controllers\Helpdesk;

use App\Http\Controllers\Controller;
use App\Services\Helpdesk\HomeService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected HomeService $homeService
    ) {
        $this->middleware('auth');
    }

    /**
     * Exibe o dashboard principal da aplicação.
     */
    public function index(): View
    {
        $data = $this->homeService->getHomeData();

        return view('home', [
            'categories'      => $data['categories'],
            'solutions'       => $data['solutions'],
            'solutions_order' => $data['latest_solutions'],
        ]);
    }
}
