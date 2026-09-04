@php
    $statusMap = [
        'pen' => ['label' => 'Pendente', 'class' => 'bg-amber-100 text-amber-800'],
        'open' => ['label' => 'Pendente', 'class' => 'bg-amber-100 text-amber-800'],
        'fin' => ['label' => 'Finalizado', 'class' => 'bg-emerald-100 text-emerald-800'],
        '1' => ['label' => 'Finalizado', 'class' => 'bg-emerald-100 text-emerald-800'],
        'can' => ['label' => 'Cancelado', 'class' => 'bg-rose-100 text-rose-800'],
    ];
@endphp

<table class="min-w-[800px] w-full divide-y divide-gray-200 dark:divide-slate-700">
    <thead class="bg-gray-50 dark:bg-slate-700">
        <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">ID</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Cliente</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Contato</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Status</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Atualizado</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">Ações</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
        @forelse($feedbacks as $feedback)
            @php
                $status = $statusMap[$feedback->status] ?? ['label' => strtoupper((string) $feedback->status), 'class' => 'bg-gray-100 text-gray-700'];
            @endphp
            <tr class="group hover:bg-blue-50 dark:hover:bg-slate-700/50 transition-colors duration-150 even:bg-gray-50/50 dark:even:bg-slate-700/20">
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $feedback->id }}</td>
                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $feedback->customer?->trade_name ?? 'Cliente não encontrado' }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                    {{ $feedback->contact ?: '-' }}
                </td>
                <td class="px-4 py-3 text-sm">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $status['class'] }}">
                        {{ $status['label'] }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                    {{ optional($feedback->updated_at)->format('d/m/Y H:i') }}
                </td>
                <td class="px-4 py-3">
                    <div class="flex justify-end gap-2">
                        <a
                            href="{{ $feedback->form_id == 1 ? route('feedback.retorno.show', ['id' => $feedback->id]) : route('feedback.create', ['feedback_id' => $feedback->id, 'form_id' => $feedback->form_id]) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center rounded-md border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 px-3 py-1.5 text-xs font-semibold text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/40">
                            Atender
                        </a>

                        <form method="POST" action="{{ route('feedback.destroy', ['id' => $feedback->id]) }}" id="form-cancel-feedback-{{ $feedback->id }}">
                            @csrf
                            @method('DELETE')
                            <button
                                type="button"
                                @click="
                                    window.confirmModal({
                                        title: 'Cancelar feedback #{{ $feedback->id }}?',
                                        message: 'O feedback será cancelado permanentemente.',
                                        confirmLabel: 'Cancelar feedback',
                                    }).then(ok => { if (ok) $el.closest('form').submit(); })
                                "
                                class="inline-flex items-center rounded-md border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 px-3 py-1.5 text-xs font-semibold text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/40">
                                Cancelar
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                    Nenhum feedback pendente para o filtro atual.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
