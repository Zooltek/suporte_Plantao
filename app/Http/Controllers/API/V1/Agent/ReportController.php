<?php

namespace App\Http\Controllers\API\V1\Agent;

use App\Http\Controllers\Controller;
use App\Models\Ticket\Agent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Constantes para evitar duplicação de literais (SonarQube S1192)
     */
    private const CLASS_CENTER = 'text-center';

    private const CLASS_BOLD = 'font-bold';

    private const CLASS_CENTER_BOLD = 'text-center font-bold';

    /**
     * direciona para o relatório específico baseado no ID.
     */
    public function generate(Request $request): View|JsonResponse
    {
        return match ($request->integer('report_id')) {
            1 => $this->porPeriodo($request),
            default => response()->json(['status' => false], 404),
        };
    }

    /**
     * gera o relatório de chamados por funcionário por período.
     */
    public function porPeriodo(Request $request): View
    {
        $start = Carbon::parse($request->input('start', now()->toDateTimeString()))->startOfDay();
        $end = Carbon::parse($request->input('end', now()->toDateTimeString()))->endOfDay();

        $funcionarios = Agent::query()
            ->where('ticketit_agent', 1)
            ->withCount([
                'tickets as pending' => fn ($q) => $q->pendingStatus(),
                'tickets as not_solved' => fn ($q) => $q->whereBetween('created_at', [$start, $end])->where('status_id', 5),
                'tickets as solved' => fn ($q) => $q->whereBetween('created_at', [$start, $end])->where('status_id', 3),
            ])
            ->get();

        $content = $funcionarios->map(function ($funcionario) {
            $total = $funcionario->pending + $funcionario->not_solved + $funcionario->solved;

            return [
                ['value' => $funcionario->name, 'class' => ''],
                ['value' => $funcionario->pending, 'class' => self::CLASS_CENTER],
                ['value' => $funcionario->not_solved, 'class' => self::CLASS_CENTER],
                ['value' => $funcionario->solved, 'class' => self::CLASS_CENTER],
                ['value' => $total, 'class' => self::CLASS_CENTER],
            ];
        })
            ->sortByDesc(fn ($item) => $item[4]['value'])
            ->values()
            ->toArray();

        // cálculo dos totais de rodapé
        $totals = [
            'pending' => $funcionarios->sum('pending'),
            'not_solved' => $funcionarios->sum('not_solved'),
            'solved' => $funcionarios->sum('solved'),
            'total' => $funcionarios->sum(fn ($f) => $f->pending + $f->not_solved + $f->solved),
        ];

        // adiciona a linha de total usando as constantes
        $content[] = [
            ['value' => 'Total', 'class' => self::CLASS_BOLD],
            ['value' => $totals['pending'], 'class' => self::CLASS_CENTER_BOLD],
            ['value' => $totals['not_solved'], 'class' => self::CLASS_CENTER_BOLD],
            ['value' => $totals['solved'], 'class' => self::CLASS_CENTER_BOLD],
            ['value' => $totals['total'], 'class' => self::CLASS_CENTER_BOLD],
        ];

        $data = [
            'title' => 'Chamados por funcionário',
            'start' => $start->format('d/m/Y'),
            'end' => $end->format('d/m/Y'),
            'header' => $this->getReportHeader(),
            'content' => $content,
        ];

        return view('agent.report.report', compact('data'));
    }

    /**
     * define o cabeçalho padronizado do relatório.
     */
    private function getReportHeader(): array
    {
        return [
            ['value' => 'Funcionário', 'class' => ''],
            ['value' => 'Pendentes', 'class' => self::CLASS_CENTER],
            ['value' => 'Não Resolvidos', 'class' => self::CLASS_CENTER],
            ['value' => 'Resolvidos', 'class' => self::CLASS_CENTER],
            ['value' => 'Total', 'class' => self::CLASS_CENTER],
        ];
    }
}
