@php
    $historyStatusMap = [
        'pen' => ['label' => 'Pendente', 'class' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'],
        'open' => ['label' => 'Pendente', 'class' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'],
        'fin' => ['label' => 'Finalizado', 'class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'],
        '1' => ['label' => 'Finalizado', 'class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'],
        'can' => ['label' => 'Cancelado', 'class' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300'],
    ];
@endphp

@if($recentFeedbacks->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum feedback recente para este cliente.</p>
@else
    <div class="space-y-2">
        @foreach($recentFeedbacks as $recentFeedback)
            @php
                $historyStatus = $historyStatusMap[$recentFeedback->status] ?? [
                    'label' => strtoupper((string) $recentFeedback->status),
                    'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                ];
            @endphp
            <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-md px-3 py-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    <span class="font-semibold">#{{ $recentFeedback->id }}</span>
                    <span class="mx-1 text-gray-300 dark:text-gray-600">|</span>
                    <span>{{ optional($recentFeedback->created_at)->format('d/m/Y H:i') ?: '-' }}</span>
                    <span class="mx-1 text-gray-300 dark:text-gray-600">|</span>
                    <span>{{ $recentFeedback->contact ?: 'Sem contato informado' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $historyStatus['class'] }}">
                        {{ $historyStatus['label'] }}
                    </span>
                    @if(!empty($recentFeedback->return_content))
                        <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/30 px-2 py-0.5 text-xs font-semibold text-blue-700 dark:text-blue-300">
                            Com retorno
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
