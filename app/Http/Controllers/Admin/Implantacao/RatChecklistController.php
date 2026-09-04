<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Implantacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Implantacao\RatChecklistItemRequest;
use App\Models\Schedule\ElementType;
use App\Services\Admin\Implantacao\RatChecklistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CRUD administrativo para itens de checklist do RAT.
 * Cada item (ElementType) pertence a um Módulo e a um Grupo.
 */
class RatChecklistController extends Controller
{
    public function __construct(
        private readonly RatChecklistService $service
    ) {}

    public function index(): View
    {
        return view('admin.implantacao.rat.index', [
            'modules' => $this->service->getModulesWithItems(),
        ]);
    }

    public function create(): View
    {
        return view('admin.implantacao.rat.form', [
            'item'    => null,
            'modules' => $this->service->getAllModules(),
            'groups'  => $this->service->getAllGroups(),
        ]);
    }

    public function store(RatChecklistItemRequest $request): RedirectResponse
    {
        try {
            $item = $this->service->createItem($request->toDto());

            return redirect()
                ->route('admin.implantacao.rat.index')
                ->with('status', "Item \"{$item->label}\" criado com sucesso.");
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('warning', $e->getMessage());
        }
    }

    public function edit(ElementType $rat): View
    {
        return view('admin.implantacao.rat.form', [
            'item'    => $rat->load('group', 'module'),
            'modules' => $this->service->getAllModules(),
            'groups'  => $this->service->getAllGroups(),
        ]);
    }

    public function update(RatChecklistItemRequest $request, ElementType $rat): RedirectResponse
    {
        try {
            $item = $this->service->updateItem($rat, $request->toDto());

            return redirect()
                ->route('admin.implantacao.rat.index')
                ->with('status', "Item \"{$item->label}\" atualizado com sucesso.");
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('warning', $e->getMessage());
        }
    }

    public function destroy(ElementType $rat): RedirectResponse
    {
        try {
            $this->service->deleteItem($rat);

            return redirect()
                ->route('admin.implantacao.rat.index')
                ->with('status', 'Item removido com sucesso.');
        } catch (\DomainException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }
}
