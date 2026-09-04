{{-- Problemas e resoluções do chamado (ticketit.trouble/solution + ticket_issues) --}}
@php
    $issues = $ticket->relationLoaded('issues') ? $ticket->issues : collect();
    $ticketProblem = trim((string) ($ticket->trouble ?? ''));
    $ticketSolution = trim((string) ($ticket->solution ?? ''));
    $hasTicketProblem = $ticketProblem !== '' || $ticketSolution !== '';
    $totalEntries = $issues->count() + ($hasTicketProblem ? 1 : 0);
@endphp

@if($totalEntries > 0)
    <div class="mt-2 space-y-1.5">
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
            Problemas e Resoluções ({{ $totalEntries }})
        </p>
        @if($hasTicketProblem)
            <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-xs">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-gray-700">
                        <span class="font-bold text-gray-900">Problema:</span>
                        {{ $ticketProblem !== '' ? $ticketProblem : 'Não informado' }}
                    </p>
                    <span class="flex-shrink-0 px-1.5 py-0.5 rounded text-[10px] font-bold
                                 {{ $ticketSolution !== '' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $ticketSolution !== '' ? 'Resolvido' : 'Pendente' }}
                    </span>
                </div>
                @if($ticketSolution !== '')
                    <p class="mt-1 text-gray-700">
                        <span class="font-bold text-emerald-700">Resolução:</span>
                        {{ $ticketSolution }}
                        @if($ticket->completed_at)
                            <span class="text-gray-400">— {{ $ticket->completed_at->format('d/m/Y H:i') }}</span>
                        @endif
                    </p>
                @endif
            </div>
        @endif
        @foreach($issues as $issue)
            <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-xs">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-gray-700">
                        <span class="font-bold text-gray-900">Problema:</span>
                        {{ $issue->title ?: $issue->description }}
                    </p>
                    <span class="flex-shrink-0 px-1.5 py-0.5 rounded text-[10px] font-bold
                                 {{ $issue->isResolved() ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $issue->isResolved() ? 'Resolvido' : 'Pendente' }}
                    </span>
                </div>
                @if($issue->title && $issue->description)
                    <p class="mt-1 text-gray-600">{{ $issue->description }}</p>
                @endif
                @if($issue->solution)
                    <p class="mt-1 text-gray-700">
                        <span class="font-bold text-emerald-700">Resolução:</span>
                        {{ $issue->solution }}
                        @if($issue->resolved_at)
                            <span class="text-gray-400">— {{ $issue->resolved_at->format('d/m/Y H:i') }}</span>
                        @endif
                    </p>
                @endif
            </div>
        @endforeach
    </div>
@endif
