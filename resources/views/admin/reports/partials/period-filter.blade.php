<div x-data="{ open: false }" class="relative no-print">
    <button type="button"
            @click="open = !open"
            @keydown.escape.window="open = false"
            :aria-expanded="open.toString()"
            class="rpt-btn border inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
        </svg>
        Filtrar
        <span class="rpt-badge-soft border rounded-md px-2 py-0.5 text-[11px] font-semibold">
            {{ $selectedPeriodPresetLabel }}
        </span>
        <svg class="h-3.5 w-3.5 transition-transform duration-150" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open"
         x-cloak
         x-transition.origin.top.right
         @click.outside="open = false"
         class="rpt-filter-panel absolute right-0 z-30 mt-2 w-80 max-w-[calc(100vw-2rem)] rounded-2xl border p-3 shadow-xl">
        <div class="space-y-3">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[.15em] rpt-sub">Período</p>
                    <p class="mt-1 text-[11px] rpt-sub">Selecione um atalho ou defina um intervalo personalizado.</p>
                </div>
                @if($hasActiveReportFilters)
                    <a href="{{ route($filterRouteName) }}"
                       class="text-xs font-semibold rpt-sub hover:underline">
                        Limpar
                    </a>
                @endif
            </div>

            <div class="space-y-1.5">
                @foreach($periodPresetOptions as $preset)
                    <a href="{{ $preset['url'] }}"
                       class="rpt-filter-option {{ $preset['active'] ? 'is-active' : '' }}">
                        <span class="block text-sm font-semibold rpt-filter-label">{{ $preset['label'] }}</span>
                        <span class="mt-1 block text-[11px] rpt-sub">{{ $preset['description'] }}</span>
                    </a>
                @endforeach
            </div>

            @if(! empty($secondaryFilters))
                <div class="border-t pt-3 rpt-divider">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[.15em] rpt-sub">Filtros adicionais</p>
                        <p class="mt-1 text-[11px] rpt-sub">Refine o relatório sem alterar o período atual.</p>
                    </div>

                    <form method="GET" action="{{ route($filterRouteName) }}" class="mt-3 space-y-2.5">
                        @foreach($currentPeriodHiddenFields as $fieldName => $fieldValue)
                            <input type="hidden" name="{{ $fieldName }}" value="{{ $fieldValue }}">
                        @endforeach

                        @include('admin.reports.partials.secondary-filter-fields', [
                            'fields' => $secondaryFilters,
                        ])

                        <button type="submit"
                                class="rpt-filter-submit inline-flex w-full items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold transition-colors">
                            Aplicar filtros
                        </button>
                    </form>
                </div>
            @endif

            <div class="border-t pt-3 rpt-divider">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[.15em] rpt-sub">Personalizado</p>
                    <p class="mt-1 text-[11px] rpt-sub">Informe as datas inicial e final para um recorte específico.</p>
                </div>

                <form method="GET" action="{{ route($filterRouteName) }}" class="mt-3 space-y-2.5">
                    <input type="hidden" name="period_preset" value="custom">
                    @foreach($secondaryHiddenFields as $fieldName => $fieldValue)
                        <input type="hidden" name="{{ $fieldName }}" value="{{ $fieldValue }}">
                    @endforeach

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <label class="space-y-1">
                            <span class="text-[11px] font-semibold rpt-sub">De</span>
                            <input type="date"
                                   name="date_from"
                                   value="{{ $dateFrom }}"
                                   class="rpt-input w-full border rounded-lg px-3 py-2 text-xs font-semibold transition-shadow">
                        </label>

                        <label class="space-y-1">
                            <span class="text-[11px] font-semibold rpt-sub">Até</span>
                            <input type="date"
                                   name="date_to"
                                   value="{{ $dateTo }}"
                                   class="rpt-input w-full border rounded-lg px-3 py-2 text-xs font-semibold transition-shadow">
                        </label>
                    </div>

                    <button type="submit"
                            class="rpt-filter-submit inline-flex w-full items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold transition-colors">
                        Aplicar período
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
