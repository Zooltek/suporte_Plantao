<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Implantacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Implantacao\ScheduleTypeRequest;
use App\Models\ScheduleType;
use App\Services\Admin\Implantacao\ScheduleTypeService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScheduleTypeController extends Controller
{
    public function __construct(
        private readonly ScheduleTypeService $service,
    ) {}

    public function index(): View
    {
        return view('admin.implantacao.schedule-types.index', [
            'types' => $this->service->getAll(),
        ]);
    }

    public function create(): View
    {
        return view('admin.implantacao.schedule-types.form', [
            'type' => null,
        ]);
    }

    public function store(ScheduleTypeRequest $request): RedirectResponse
    {
        $type = $this->service->create($request->validated());

        return redirect()
            ->route('admin.implantacao.schedule-types.index')
            ->with('status', "Tipo \"{$type->label}\" criado com sucesso.");
    }

    public function edit(ScheduleType $scheduleType): View
    {
        return view('admin.implantacao.schedule-types.form', [
            'type' => $scheduleType,
        ]);
    }

    public function update(ScheduleTypeRequest $request, ScheduleType $scheduleType): RedirectResponse
    {
        $type = $this->service->update($scheduleType, $request->validated());

        return redirect()
            ->route('admin.implantacao.schedule-types.index')
            ->with('status', "Tipo \"{$type->label}\" atualizado com sucesso.");
    }

    public function destroy(ScheduleType $scheduleType): RedirectResponse
    {
        try {
            $this->service->delete($scheduleType);

            return redirect()
                ->route('admin.implantacao.schedule-types.index')
                ->with('status', 'Tipo removido com sucesso.');
        } catch (DomainException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }
}
