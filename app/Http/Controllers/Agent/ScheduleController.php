<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\ScheduleTypeRepositoryInterface;
use App\Http\Requests\Agent\SaveScheduleRequest;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\Agent;
use App\Models\Schedule\Module;
use App\Services\Agent\ScheduleService;
use App\Services\Access\AccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use DomainException;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly ScheduleService $scheduleService,
        private readonly AccessService $accessService,
        private readonly ScheduleTypeRepositoryInterface $scheduleTypeRepository,
    ) {}

    public function index(): RedirectResponse
    {
        return redirect()->route('agent.calendar.condensed', ['active' => 'schedules']);
    }

    public function create(Request $request): View
    {
        $sourceTicket = $request->filled('ticket_id')
            ? Ticket::query()->with('company')->findOrFail($request->integer('ticket_id'))
            : null;

        return view('agent.schedule.create', [
            'customers'     => Customer::orderBy('trade_name', 'asc')->get(),
            'agents'        => Agent::active()->where('ticketit_agent', '!=', 0)->orderBy('name', 'asc')->get(),
            'modules'       => Module::orderBy('project')->orderBy('name')->get()->groupBy(fn ($m) => $m->project ?? 'Geral'),
            'sourceTicket'  => $sourceTicket,
            'currentUser'   => Auth::guard('admin')->user(),
            'isAdmin'       => $this->accessService->isAdmin(Auth::guard('admin')->user()),
            'scheduleTypes' => $this->scheduleTypeRepository->getActive(),
        ]);
    }

    public function store(SaveScheduleRequest $request): RedirectResponse
    {
        $schedule = $this->scheduleService->createSchedule($request->validated());

        return redirect()
            ->route('agent.calendar.condensed', ['active' => 'schedules'])
            ->with('success', $schedule->needsAdminConfirmation()
                ? 'Agendamento lançado e enviado para confirmação do administrador.'
                : 'Agendamento salvo com sucesso.');
    }

    public function show(Schedule $schedule): View
    {
        $schedule->loadMissing(['agent', 'customer', 'ticket.company', 'module', 'records.agent', 'records.module']);

        return view('agent.schedule.show', [
            'schedule' => $schedule,
            'isAdmin'  => $this->accessService->isAdmin(Auth::guard('admin')->user()),
        ]);
    }

    public function edit(Schedule $schedule): View
    {
        $schedule->loadMissing(['ticket.company']);

        return view('agent.schedule.create', [
            'schedule'      => $schedule,
            'customers'     => Customer::orderBy('trade_name', 'asc')->get(),
            'agents'        => Agent::active()->where('ticketit_agent', '!=', 0)->orderBy('name', 'asc')->get(),
            'modules'       => Module::orderBy('project')->orderBy('name')->get()->groupBy(fn ($m) => $m->project ?? 'Geral'),
            'sourceTicket'  => $schedule->ticket,
            'currentUser'   => Auth::guard('admin')->user(),
            'isAdmin'       => $this->accessService->isAdmin(Auth::guard('admin')->user()),
            'scheduleTypes' => $this->scheduleTypeRepository->getActive(),
        ]);
    }

    public function update(SaveScheduleRequest $request, Schedule $schedule): RedirectResponse
    {
        try {
            $this->scheduleService->updateSchedule($schedule, $request->validated());

            return redirect()
                ->route('agent.calendar.condensed', ['active' => 'schedules'])
                ->with('success', 'Agendamento atualizado com sucesso.');

        } catch (DomainException $e) {
            return redirect()
                ->route('agent.schedules.edit', $schedule->id)
                ->with('warning', $e->getMessage());
        }
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user->can('delete', $schedule), 403, 'Apenas administradores podem excluir agendamentos.');

        try {
            $this->scheduleService->deleteSchedule($schedule);

            return redirect()
                ->route('agent.calendar.condensed', ['active' => 'schedules'])
                ->with('success', 'Agendamento excluído com sucesso.');

        } catch (DomainException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }

    public function confirm(Schedule $schedule): RedirectResponse
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user->can('confirm', $schedule), 403, 'Apenas administradores podem confirmar este agendamento.');

        try {
            $this->scheduleService->confirmSchedule($schedule);

            return back()->with('success', 'Agendamento confirmado com sucesso.');
        } catch (DomainException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }

    public function confirmOwn(Schedule $schedule): RedirectResponse
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user->can('confirmOwn', $schedule), 403, 'Você não tem permissão para confirmar este agendamento.');

        try {
            $this->scheduleService->confirmScheduleByAgent($schedule);

            return back()->with('success', 'Agendamento confirmado com sucesso.');
        } catch (DomainException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }

    public function cancel(Schedule $schedule): RedirectResponse
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user->can('cancel', $schedule), 403, 'Você não tem permissão para cancelar este agendamento.');

        try {
            $this->scheduleService->cancelSchedule($schedule);

            return back()->with('success', 'Agendamento cancelado.');
        } catch (DomainException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }

    public function finalize(Schedule $schedule): RedirectResponse
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user->can('finalize', $schedule), 403, 'Você não tem permissão para finalizar este agendamento.');

        try {
            $this->scheduleService->finalizeSchedule($schedule);

            return back()->with('success', 'Agendamento finalizado com sucesso.');
        } catch (DomainException $e) {
            return back()->with('warning', $e->getMessage());
        }
    }
}
