<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyModuleType;
use App\Models\Customer;
use App\Models\Ticket\Agent;
use App\Services\Agent\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class CompanyController extends Controller
{
    /**
     * Injeção de Dependência do Serviço no Construtor.
     */
    public function __construct(
        protected CompanyService $companyService
    ) {}

    /**
     * Exibe o histórico consolidado da empresa.
     */
    public function history(int|string $id, Request $request): View
    {
        $company = Company::with('moduleTypes', 'contacts', 'software', 'group', 'state')->findOrFail($id);

        $data = $this->companyService->getCompanyHistory($company, $request->all());

        // Se for requisição AJAX (fetch do Alpine), retorna apenas o conteúdo sem layout
        if ($request->ajax() || $request->wantsJson()) {
            return view('agent.company.history-partial', $data);
        }

        $data['agents'] = Agent::where('ticketit_agent', 1)->orderBy('name')->get();
        $data['categories'] = Category::query()
            ->with('description')
            ->root()
            ->orderedByDisplayName()
            ->get();

        return view('agent.company.history', $data);
    }

    /**
     * Tela de pesquisa simples de clientes.
     */
    public function search(): View
    {
        $customers = Customer::orderBy('trade_name', 'asc')->get();

        return view('agent.customer.search', compact('customers'));
    }

    /**
     * Listagem principal com filtros.
     */
    public function manageIndex(Request $request): View
    {
        $companies = $this->companyService->listCompanies($request->all());

        return view('agent.companies.index', compact('companies'));
    }

    public function manageSearch(): View
    {
        return view('agent.companies.search');
    }

    public function manageCreate(): View
    {
        $moduleTypes = CompanyModuleType::where('is_active', true)->orderBy('sort_order')->get();

        return view('agent.companies.create', compact('moduleTypes'));
    }

    /**
     * Persistência de nova empresa (Validação via StoreCompanyRequest).
     */
    public function manageStore(StoreCompanyRequest $request): RedirectResponse
    {
        $data = Arr::except($request->validated(), ['contacts', 'module_ids']);
        $company = Company::create($data);

        $this->syncContacts($company, $request->input('contacts', []));
        $company->moduleTypes()->sync($request->input('module_ids', []));

        return redirect()
            ->route('agent.companies.manage.index')
            ->with('success', 'Cliente criado com sucesso!');
    }

    public function manageEdit(Company $company): View
    {
        $company->load('contacts', 'moduleTypes');
        $moduleTypes = CompanyModuleType::where('is_active', true)->orderBy('sort_order')->get();
        $activeModuleIds = $company->moduleTypes->pluck('id')->toArray();

        return view('agent.companies.edit', compact('company', 'moduleTypes', 'activeModuleIds'));
    }

    /**
     * Atualização de empresa existente.
     */
    public function manageUpdate(StoreCompanyRequest $request, Company $company): RedirectResponse
    {
        $data = Arr::except($request->validated(), ['contacts', 'module_ids']);
        $company->update($data);

        $this->syncContacts($company, $request->input('contacts', []));
        $company->moduleTypes()->sync($request->input('module_ids', []));

        return redirect()
            ->route('agent.companies.manage.index')
            ->with('success', 'Cliente atualizado com sucesso!');
    }

    private function syncContacts(Company $company, array $contacts): void
    {
        $company->contacts()->where('origin', 'support')->delete();

        // Remove somente linhas manuais vazias; contatos do Financeiro são preservados.
        $contacts = array_values(array_filter(
            $contacts,
            fn ($contact) => ! empty($contact['name']) || ! empty($contact['phone']) || ! empty($contact['email']),
        ));

        if (empty($contacts)) {
            return;
        }

        // Garante que apenas um contato seja principal
        $hasMain = collect($contacts)->contains(fn ($c) => ! empty($c['is_main']));

        foreach ($contacts as $i => $contact) {
            $company->contacts()->create([
                'name' => $contact['name'] ?? '',
                'phone' => ($contact['phone'] ?? '') ?: null,
                'email' => ($contact['email'] ?? '') ?: null,
                'origin' => 'support',
                'is_main' => $hasMain ? ! empty($contact['is_main']) : ($i === 0),
            ]);
        }
    }

    public function manageDestroy(Company $company): RedirectResponse
    {
        $company->delete();

        return redirect()
            ->route('companies.manage.index')
            ->with('success', 'Cliente removido com sucesso!');
    }

    /**
     * API para busca dinâmica (autocomplete/filtros avançados).
     */
    public function apiSearch(Request $request): JsonResponse
    {
        $companies = $this->companyService->searchApi($request->all());

        return response()->json($companies);
    }

    /**
     * Métodos Toggle (Refatorados para usar um único método no Service).
     */
    public function toggleActive(Company $company): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'A situação do cliente é controlada pelo financeiro.',
        ], 403);
    }

    public function toggleEcommerce(Company $company): JsonResponse
    {
        $status = $this->companyService->toggleAttribute($company, 'has_ecommerce');

        return response()->json([
            'success' => true,
            'has_ecommerce' => $status,
            'message' => $status ? 'E-commerce adicionado!' : 'E-commerce removido!',
        ]);
    }

    public function toggleCrm(Company $company): JsonResponse
    {
        $status = $this->companyService->toggleAttribute($company, 'has_crm');

        return response()->json([
            'success' => true,
            'has_crm' => $status,
            'message' => $status ? 'CRM adicionado!' : 'CRM removido!',
        ]);
    }

    public function toggleTef(Company $company): JsonResponse
    {
        $status = $this->companyService->toggleAttribute($company, 'has_tef');

        return response()->json([
            'success' => true,
            'has_tef' => $status,
            'message' => $status ? 'TEF adicionado!' : 'TEF removido!',
        ]);
    }
}
