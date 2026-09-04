<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\StoreRecordRequest;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\Schedule\Module;
use App\Models\Schedule\Record;
use App\Models\Ticket\Agent;
use App\Services\Access\AccessService;
use App\Services\Admin\Implantacao\ScheduleModuleConfigService;
use App\Services\Agent\RecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class RecordController extends Controller
{
    public function __construct(
        private readonly RecordService $recordService,
        private readonly AccessService $accessService,
        private readonly ScheduleModuleConfigService $moduleConfigService
    ) {}

    public function index(int $id): View
    {
        $records = Record::active()
            ->where('schedule_id', $id)
            ->with('elements')
            ->get();

        return view('agent.schedule.record.list', ['records' => $records, 'scheduleId' => $id]);
    }

    public function create(int $id): View
    {
        $schedule = Schedule::with('customer')->findOrFail($id);
        $modules = $this->resolveModules($schedule);

        return view('agent.schedule.record.create', [
            'schedule' => $schedule,
            'agents' => Agent::active()->get(),
            'modules' => $modules,
            'customers' => $schedule->customer ? null : Customer::orderBy('trade_name')->get(),
        ]);
    }

    public function edit(int $id, int $record_id): View
    {
        $schedule = Schedule::with('customer')->findOrFail($id);
        $modules = $this->resolveModules($schedule);

        return view('agent.schedule.record.create', [
            'record' => Record::active()->findOrFail($record_id),
            'schedule' => $schedule,
            'agents' => Agent::active()->get(),
            'modules' => $modules,
        ]);
    }

    /**
     * Retorna os módulos disponíveis para o agendamento, filtrados pelo cliente.
     * Fallback: todos os módulos quando o cliente não tem configuração.
     */
    private function resolveModules(Schedule $schedule): \Illuminate\Support\Collection
    {
        $company = $schedule->customer_id
            ? Company::find($schedule->customer_id)
            : null;

        return $company
            ? $this->moduleConfigService->getForCompany($company)
                ->groupBy(fn ($m) => $m->project ?? 'Geral')
            : Module::orderBy('project')->orderBy('name')->get()
                ->groupBy(fn ($m) => $m->project ?? 'Geral');
    }

    public function store(int $id, StoreRecordRequest $request): RedirectResponse
    {
        $schedule = Schedule::findOrFail($id);

        try {
            $this->recordService->createRecord(
                $schedule,
                $request->validated(),
                $request->all()
            );

            return redirect()
                ->route('agent.schedules.show', $id)
                ->with('success', 'RAT registrado com sucesso.');

        } catch (\Exception $e) {
            Log::error("Erro ao salvar record [Schedule ID: {$id}]: ".$e->getMessage());

            return back()->withInput()->withErrors('Erro interno ao salvar o registro. Tente novamente.');
        }
    }

    public function destroy(int $id, int $record_id): RedirectResponse|JsonResponse
    {
        $user = Auth::guard('admin')->user();
        $record = Record::active()->findOrFail($record_id);

        abort_unless($user->can('delete', $record), 403, 'Apenas administradores podem excluir RATs.');
        $record->update(['status' => 0]);

        return redirect()->route('agent.record.index', ['schedule' => $id]);
    }

    public function print(int $id, int $record_id): View
    {
        $data = $this->recordService->getPrintData($record_id);

        return view('agent.schedule.record.print', $data);
    }
}
