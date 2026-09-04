<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Implantacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Implantacao\RatGroupRequest;
use App\Models\Schedule\ElementGroup;
use App\Services\Admin\Implantacao\RatGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RatGroupController extends Controller
{
    public function __construct(
        private readonly RatGroupService $service
    ) {}

    public function index(): View
    {
        return view('admin.implantacao.groups.index', [
            'groups' => $this->service->getAllWithItemCount(),
        ]);
    }

    public function create(): View
    {
        return view('admin.implantacao.groups.form', [
            'group' => null,
        ]);
    }

    public function store(RatGroupRequest $request): RedirectResponse
    {
        $group = $this->service->createGroup($request->validated());

        return redirect()
            ->route('admin.implantacao.groups.index')
            ->with('status', "Grupo \"{$group->name}\" criado com sucesso.");
    }

    public function edit(ElementGroup $group): View
    {
        return view('admin.implantacao.groups.form', [
            'group' => $group,
        ]);
    }

    public function update(RatGroupRequest $request, ElementGroup $group): RedirectResponse
    {
        $group = $this->service->updateGroup($group, $request->validated());

        return redirect()
            ->route('admin.implantacao.groups.index')
            ->with('status', "Grupo \"{$group->name}\" atualizado com sucesso.");
    }

    public function destroy(ElementGroup $group): RedirectResponse
    {
        try {
            $this->service->deleteGroup($group);

            return redirect()
                ->route('admin.implantacao.groups.index')
                ->with('status', 'Grupo removido com sucesso.');
        } catch (\DomainException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }
}
