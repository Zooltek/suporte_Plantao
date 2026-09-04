<?php

namespace App\Http\Controllers\Helpdesk;

use App\Http\Controllers\Controller;
use App\Services\Helpdesk\CompanyService;
use App\Http\Requests\CompanyCreateRequest;
use App\Http\Requests\VinculoUpdateRequest;
use App\Http\Requests\GetCitiesRequest;
use App\Models\User;
use App\Models\State;
use App\Models\City;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    public function __construct(
        protected CompanyService $companyService
    ) {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $companies = $this->companyService->getAllCompanies();
        return view('admin.company.index', compact('companies'));
    }

    public function create(): View
    {
        $users = User::where('company_id', 0)->get();
        $states = State::all();
        $cities = City::all(); // Ou carregar via AJAX dinamicamente

        return view('admin.company.create', compact('users', 'states', 'cities'));
    }

    public function store(CompanyCreateRequest $request): RedirectResponse
    {
        $this->companyService->storeCompany($request->validated());

        return redirect()->route('company.index')
            ->with('status', 'Empresa criada com sucesso.');
    }

    public function userVinculo(): View
    {
        $users = User::all();
        $companies = $this->companyService->getAllCompanies();

        return view('admin.company.vinculo', compact('users', 'companies'));
    }

    public function vincular(VinculoUpdateRequest $request): RedirectResponse
    {
        $this->companyService->linkUserToCompany(
            $request->user,
            $request->company,
            $request->boolean('responsible')
        );

        return redirect()->route('company.index')
            ->with('status', 'Usuário vinculado com sucesso.');
    }

    /**
     * Retorna cidades em JSON (substituindo a montagem de HTML manual no controller).
     */
    public function getCities(GetCitiesRequest $request): JsonResponse
    {
        $cities = $this->companyService->getCitiesByState($request->state_id);
        
        return response()->json($cities);
    }
}
