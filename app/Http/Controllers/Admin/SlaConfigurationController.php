<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\Helpdesk\Ticketit\SlaServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSlaSettingsRequest;
use App\Services\Admin\SlaFormService;
use App\Exceptions\SlaConfigurationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SlaConfigurationController extends Controller
{
    public function __construct(
        protected SlaServiceInterface $slaService,
        protected SlaFormService $formService
    ) {}

    public function index(): View
    {
        $thresholds = $this->slaService->getThresholdsMatrix();

        return view('admin.sla.index', [
            'rows' => $this->formService->buildRows(
                $thresholds,
                (array) session()->getOldInput()
            ),
        ]);
    }

    public function update(UpdateSlaSettingsRequest $request): RedirectResponse
    {
        try {
            $this->slaService->updateThresholds($request->validated());
        } catch (SlaConfigurationException $exception) {
            return back()
                ->withInput()
                ->withErrors(['sla' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.sla.index')
            ->with('success', 'Tempos de SLA atualizados com sucesso.');
    }
}
