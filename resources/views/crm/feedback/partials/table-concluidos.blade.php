<table class="min-w-[800px] w-full divide-y divide-gray-200 dark:divide-slate-700">
    <thead class="bg-gray-50 dark:bg-slate-700">
        <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">ID</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Cliente</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Finalizado em</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Retorno</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Status</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Ações</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
        @forelse($feedbacks as $feedback)
            <tr class="group hover:bg-blue-50 dark:hover:bg-slate-700/50 transition-colors duration-150 even:bg-gray-50/50 dark:even:bg-slate-700/20">
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $feedback->id }}</td>
                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $feedback->customer?->trade_name ?? 'Cliente não encontrado' }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                    {{ optional($feedback->completed_at)->format('d/m/Y H:i') ?: '-' }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    {{ \Illuminate\Support\Str::limit($feedback->return_content ?? '-', 80) }}
                </td>
                <td class="px-4 py-3 text-sm">
                    @if(!empty($feedback->return_content))
                        <span class="inline-flex items-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 dark:text-emerald-300">
                            Respondido
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-slate-100 dark:bg-slate-700 px-2.5 py-0.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Sem retorno
                        </span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    <a
                        href="{{ route('feedback.retorno.show', ['id' => $feedback->id]) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center rounded-md border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 px-3 py-1.5 text-xs font-semibold text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/40">
                        Responder
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    Nenhum feedback concluído para o filtro atual.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
