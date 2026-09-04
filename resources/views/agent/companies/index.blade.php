@extends('layouts.agent')

@section('title', 'Empresas - Agente')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div x-data="companySearch()" @keydown.window.escape="clearSearch()" class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Empresas</h1>
            <p class="text-sm text-gray-500 mt-0.5"><span x-text="totalText">{{ $companies->total() }}</span> cliente(s) cadastrado(s)</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('agent.companies.manage.search') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 4 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Busca Rápida
            </a>
            @if(auth('admin')->user()->ticketit_admin)
            <a href="{{ route('agent.companies.manage.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Nova Empresa
            </a>
            @endif
        </div>
    </div>

    {{-- Search via AJAX - sem refresh da página --}}
    <div class="relative">
        <div class="flex items-center gap-3 p-3 bg-white rounded-2xl border border-gray-200 shadow-sm transition-all duration-200 focus-within:border-orange-300 focus-within:ring-4 focus-within:ring-orange-100">

            {{-- Search icon / loading spinner --}}
            <div class="pl-1.5 flex-shrink-0">
                <svg x-show="!isSearching" class="h-4.5 w-4.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <svg x-show="isSearching" x-cloak class="h-4.5 w-4.5 text-orange-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </div>

            {{-- Input --}}
            <input type="search" name="search"
                   x-model="search"
                   x-ref="searchInput"
                   @input.debounce.300ms="handleSearchInput()"
                   @keydown.enter.prevent="performSearch(true)"
                   placeholder="Buscar por nome, CNPJ, cidade ou contato..."
                   aria-label="Buscar empresas"
                   autocomplete="off"
                   class="flex-1 text-sm bg-transparent border-none outline-none focus:ring-0 placeholder-gray-400 text-gray-900 py-1">

            {{-- Clear button --}}
            <button x-show="search.length > 0 || hasActiveSearch" x-cloak
                    @click="clearSearch()"
                    type="button"
                    class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                    title="Limpar busca">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            {{-- Filter button --}}
            <button type="button"
                    @click="performSearch(true)"
                    :disabled="isSearching || normalizedSearch.length < minSearchLength"
                    :class="isSearching || normalizedSearch.length < minSearchLength ? 'bg-gray-200 text-gray-400 cursor-not-allowed shadow-none' : 'bg-gray-900 hover:bg-gray-800 text-white shadow-sm'"
                    class="px-5 py-2 text-xs font-bold uppercase tracking-wider rounded-xl transition-colors">
                Buscar
            </button>
        </div>

        <div class="mt-2 min-h-5 flex flex-wrap items-center gap-2 text-xs text-gray-500" aria-live="polite">
            <template x-if="isSearching">
                <span>Buscando empresas...</span>
            </template>

            <template x-if="!isSearching && errorMessage">
                <div class="flex items-center gap-2 text-red-600">
                    <span x-text="errorMessage"></span>
                    <button type="button" @click="performSearch(true)" class="font-semibold hover:text-red-700 transition-colors">
                        Tentar novamente
                    </button>
                </div>
            </template>

            <template x-if="!isSearching && !errorMessage && normalizedSearch.length > 0 && normalizedSearch.length < minSearchLength">
                <span>Digite ao menos <span x-text="minSearchLength"></span> caracteres para buscar.</span>
            </template>

            <template x-if="!isSearching && !errorMessage && hasActiveSearch">
                <div class="flex items-center gap-2">
                    <span><strong class="text-gray-700" x-text="totalText"></strong> resultado(s) para "<strong class="text-gray-700" x-text="lastSearch"></strong>"</span>
                    <span class="text-gray-300">·</span>
                    <button type="button" @click="clearSearch()" class="text-orange-500 hover:text-orange-600 font-semibold transition-colors">
                        Ver todos
                    </button>
                </div>
            </template>
        </div>
    </div>

    {{-- Company Table --}}
    <div class="relative overflow-hidden bg-white rounded-2xl border border-gray-200 shadow-sm" x-ref="tableContainer" :aria-busy="isSearching ? 'true' : 'false'">
        <div x-show="isSearching" x-cloak class="absolute inset-x-0 top-0 z-10 h-0.5 bg-orange-100">
            <div class="h-full w-1/2 bg-orange-500 animate-pulse"></div>
        </div>

        <template x-if="isSearching && companies.length === 0">
            <div class="py-20 text-center">
                <svg class="animate-spin h-8 w-8 text-orange-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                <p class="text-gray-500 text-sm">Buscando empresas...</p>
            </div>
        </template>

        <template x-if="!isSearching && companies.length === 0 && !hasSearched">
            <div class="py-20 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <p class="text-gray-700 font-semibold">Nenhuma empresa cadastrada</p>
            </div>
        </template>

        <template x-if="!isSearching && companies.length === 0 && hasSearched">
            <div class="py-20 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <p class="text-gray-700 font-semibold">Nenhuma empresa encontrada</p>
                <p class="text-gray-400 text-sm mt-1">Tente ajustar o filtro de busca.</p>
            </div>
        </template>

        <template x-if="companies.length > 0">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400">Empresa</th>
                        <th class="px-4 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 hidden md:table-cell">CNPJ</th>
                        <th class="px-4 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 hidden lg:table-cell">Cidade / UF</th>
                        <th class="px-4 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-gray-400 hidden lg:table-cell">Telefone</th>
                        <th class="px-4 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400 w-24">Status</th>
                        <th class="px-4 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider text-gray-400 w-32">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 transition-opacity duration-150" :class="{ 'opacity-60': isSearching }">
                    <template x-for="company in companies" :key="company.id">
                        <tr class="group hover:bg-orange-50/30 transition-colors duration-150">
                            <td class="px-5 py-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-gray-900 group-hover:text-orange-700 transition-colors" x-text="company.trade_name"></p>
                                        <template x-if="company.financial_irregular">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-100 text-red-700 text-[10px] font-bold rounded-full border border-red-200 shrink-0"
                                                  title="Irregularidade financeira">
                                                <svg class="h-3 w-3 fill-red-500" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                                Irregular
                                            </span>
                                        </template>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-0.5" x-text="company.name"></p>
                                </div>
                            </td>
                            <td class="px-4 py-4 hidden md:table-cell">
                                <span class="font-mono text-xs text-gray-600" x-text="company.cnpj"></span>
                            </td>
                            <td class="px-4 py-4 hidden lg:table-cell text-gray-500 text-xs">
                                <template x-if="company.city || company.state_abbr">
                                    <span><span x-text="company.city"></span><template x-if="company.city && company.state_abbr"> / </template><span class="font-semibold" x-text="company.state_abbr"></span></span>
                                </template>
                                <template x-if="!company.city && !company.state_abbr"> — </template>
                            </td>
                            <td class="px-4 py-4 hidden lg:table-cell text-gray-500 text-xs" x-text="company.phone || '—'"></td>
                            <td class="px-4 py-4 text-center">
                                <div class="flex flex-col items-center gap-1.5">
                                    <template x-if="company.is_active">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-full border border-emerald-200">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> Ativo
                                        </span>
                                    </template>
                                    <template x-if="!company.is_active">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 text-gray-500 text-xs font-bold rounded-full border border-gray-200">
                                            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> Inativo
                                        </span>
                                    </template>
                                    <template x-if="company.financial_irregular">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-50 text-red-700 text-xs font-bold rounded-full border border-red-200">
                                            <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span> Inadimplente
                                        </span>
                                    </template>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a :href="`/support/company/${company.id}/history`"
                                       class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                       title="Ver histórico">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </a>
                                    @if(auth('admin')->user()->ticketit_admin)
                                        <a :href="`/support/companies/${company.id}/edit`"
                                           class="p-1.5 text-gray-400 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors"
                                           title="Editar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </template>

        <template x-if="!isSearching && companies.length > 0 && totalPages > 1">
            <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
                <div class="flex items-center justify-between">
                    <div class="text-xs text-gray-500">
                        Página <span x-text="currentPage"></span> de <span x-text="totalPages"></span>
                    </div>
                    <div class="flex gap-1">
                        <button type="button" @click="prevPage()" :disabled="currentPage <= 1"
                                :class="currentPage <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                                class="px-3 py-1 rounded-lg text-xs font-medium text-gray-600 transition-colors">
                            Anterior
                        </button>
                        <button type="button" @click="nextPage()" :disabled="currentPage >= totalPages"
                                :class="currentPage >= totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'"
                                class="px-3 py-1 rounded-lg text-xs font-medium text-gray-600 transition-colors">
                            Próxima
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

</div>
@endsection

@push('scripts')
<script>
function companySearch() {
    return {
        search: @js((string) request('search', '')),
        initialCompanies: @json($companies->items()),
        initialTotalText: '{{ $companies->total() }}',
        initialCurrentPage: {{ $companies->currentPage() }},
        initialTotalPages: {{ $companies->lastPage() }},
        companies: @json($companies->items()),
        isSearching: false,
        hasSearched: {{ request('search') ? 'true' : 'false' }},
        errorMessage: '',
        activeSearchController: null,
        activeRequestId: 0,
        minSearchLength: 2,
        totalText: '{{ $companies->total() }}',
        currentPage: {{ $companies->currentPage() }},
        totalPages: {{ $companies->lastPage() }},
        lastSearch: @js((string) request('search', '')),

        init() {
            if (this.normalizedSearch.length < this.minSearchLength) {
                this.hasSearched = false;
                this.lastSearch = '';
            }
        },

        get normalizedSearch() {
            return this.search.trim();
        },

        get hasActiveSearch() {
            return this.lastSearch.trim().length >= this.minSearchLength;
        },

        handleSearchInput() {
            this.errorMessage = '';

            if (this.normalizedSearch.length === 0) {
                this.clearSearch({ focus: false });
                return;
            }

            if (this.normalizedSearch.length < this.minSearchLength) {
                this.abortActiveSearch();
                this.restoreInitialResults();
                this.hasSearched = false;
                this.lastSearch = '';
                this.updateBrowserUrl('');
                return;
            }

            this.performSearch();
        },

        performSearch(force = false) {
            const term = this.normalizedSearch;

            if (term.length === 0) {
                this.clearSearch({ focus: false });
                return;
            }

            if (term.length < this.minSearchLength) {
                return;
            }

            if (!force && term === this.lastSearch && !this.errorMessage) {
                return;
            }

            this.abortActiveSearch();

            const requestId = ++this.activeRequestId;
            const controller = new AbortController();

            this.activeSearchController = controller;
            this.isSearching = true;
            this.errorMessage = '';
            this.hasSearched = true;

            fetch(`{{ route('agent.api.v1.companies.search') }}?q=${encodeURIComponent(term)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: controller.signal,
            })
                .then(r => {
                    if (!r.ok) {
                        throw new Error('search_failed');
                    }

                    return r.json();
                })
                .then(data => {
                    if (requestId !== this.activeRequestId) {
                        return;
                    }

                    this.companies = Array.isArray(data) ? data : [];
                    this.totalText = this.companies.length;
                    this.currentPage = 1;
                    this.totalPages = 1;
                    this.lastSearch = term;
                    this.updateBrowserUrl(term);
                })
                .catch(error => {
                    if (error.name === 'AbortError' || requestId !== this.activeRequestId) {
                        return;
                    }

                    this.errorMessage = 'Não foi possível atualizar a busca.';
                })
                .finally(() => {
                    if (requestId !== this.activeRequestId) {
                        return;
                    }

                    this.isSearching = false;
                    this.activeSearchController = null;
                });
        },

        clearSearch(options = {}) {
            const shouldFocus = options.focus !== false;

            this.abortActiveSearch();
            this.search = '';
            this.errorMessage = '';
            this.hasSearched = false;
            this.lastSearch = '';
            this.restoreInitialResults();
            this.updateBrowserUrl('');

            if (shouldFocus) {
                this.$nextTick(() => this.$refs.searchInput?.focus());
            }
        },

        restoreInitialResults() {
            this.companies = [...this.initialCompanies];
            this.totalText = this.initialTotalText;
            this.currentPage = this.initialCurrentPage;
            this.totalPages = this.initialTotalPages;
        },

        abortActiveSearch() {
            if (this.activeSearchController) {
                this.activeSearchController.abort();
                this.activeSearchController = null;
            }

            this.isSearching = false;
        },

        updateBrowserUrl(term) {
            const url = new URL(window.location.href);

            if (term) {
                url.searchParams.set('search', term);
            } else {
                url.searchParams.delete('search');
            }

            url.searchParams.delete('page');
            window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);
        },

        prevPage() {
            if (this.currentPage <= 1) return;
            this.loadPage(this.currentPage - 1);
        },

        nextPage() {
            if (this.currentPage >= this.totalPages) return;
            this.loadPage(this.currentPage + 1);
        },

        loadPage(page) {
            this.isSearching = true;
            const params = new URLSearchParams({ page, search: this.search });
            window.location.search = params.toString();
        }
    }
}
</script>
@endpush
