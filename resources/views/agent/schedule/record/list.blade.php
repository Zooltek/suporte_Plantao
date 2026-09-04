@extends('layouts.agent')

@section('title', 'Lista de Registros do Atendimento')

@section('content')
<div class="px-4 animate-fade-in-up" x-data="globalThis.RecordListActions">
    <div class="max-w-[1400px] mx-auto space-y-6">

        {{-- Configurações injetadas para o JS --}}
        <input type="hidden" id="js-api-config" data-sync-url="{{ url('api/v1/schedules/' . $scheduleId . '/records') }}">
        {{-- Header Título --}}
        <div class="pb-2 flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-200  gap-4">
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight text-gray-900  flex items-center gap-3">
                    <span class="p-2 bg-indigo-50  text-indigo-500 rounded-xl shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                    </span>
                    Fichas / Registros do Agendamento
                </h2>
                <p class="mt-1 text-sm text-gray-500 ">Aqui estão listados todos os registros (fichas) vinculados a este atendimento.</p>
            </div>
        </div>

        @include('shared.errors')

        {{-- Tabela Premium Glassmorphism --}}
        <div class="bg-white  rounded-2xl shadow-xl shadow-gray-200/50  border border-gray-200  overflow-hidden relative">
            
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-500 to-cyan-400"></div>

            <div class="overflow-x-auto mt-1">
                <table class="w-full text-left border-collapse" id="record_table">
                    <thead>
                        <tr class="bg-gray-50/80  border-b border-gray-200  text-xs tracking-wider text-gray-500  uppercase font-bold">
                            <th class="p-4 pl-6 font-semibold whitespace-nowrap">Cliente</th>
                            <th class="p-4 font-semibold whitespace-nowrap">Módulo</th>
                            <th class="p-4 font-semibold whitespace-nowrap text-center">Data</th>
                            <th class="p-4 font-semibold whitespace-nowrap text-center">Início</th>
                            <th class="p-4 font-semibold whitespace-nowrap text-center">Fim</th>
                            <th class="p-4 font-semibold whitespace-nowrap">Suporte (Agente)</th>
                            <th class="p-4 font-semibold whitespace-nowrap">Funcionário</th>
                            <th class="p-4 font-semibold max-w-[200px]">Observação</th>
                            <th class="p-4 pr-6 font-semibold text-center whitespace-nowrap">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 ">
                        @forelse($records as $record)
                        <tr class="hover:bg-gray-50  transition-colors group">
                            
                            <td class="p-4 pl-6 align-top">
                                <div class="font-bold text-gray-800  text-sm">
                                    {{ $record->schedule?->customer?->trade_name ?? $record->customer?->trade_name ?? 'NÃO IDENTIFICADO' }}
                                </div>
                            </td>
                            
                            <td class="p-4 align-top">
                                @if($record->module?->name)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200    shadow-sm whitespace-nowrap">
                                        {{ Str::upper($record->module?->name) }}
                                    </span>
                                @else
                                    <span class="text-gray-400 italic text-sm">-</span>
                                @endif
                            </td>
                            
                            <td class="p-4 align-top text-center text-sm font-medium text-gray-700  whitespace-nowrap">
                                {{ $record->start?->format('d/m/Y') }}
                            </td>
                            
                            <td class="p-4 align-top text-center text-sm font-medium text-gray-500  whitespace-nowrap">
                                {{ $record->start?->format('H:i') }}
                            </td>
                            
                            <td class="p-4 align-top text-center text-sm font-medium text-gray-500  whitespace-nowrap">
                                {{ $record->end?->format('H:i') }}
                            </td>
                            
                            <td class="p-4 align-top">
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 rounded-full bg-gradient-to-br from-gray-200 to-gray-300   flex items-center justify-center text-gray-500  text-[10px] font-bold shadow-sm flex-shrink-0">
                                        {{ strtoupper(substr($record->agent?->name ?? '?', 0, 1)) }}
                                    </div>
                                    <span class="text-sm font-medium text-gray-700  whitespace-nowrap">
                                        {{ $record->agent?->name ?? 'Não Definido' }}
                                    </span>
                                </div>
                            </td>
                            
                            <td class="p-4 align-top text-sm text-gray-600  whitespace-nowrap">
                                {{ $record->contact }}
                            </td>
                            
                            <td class="p-4 align-top text-sm text-gray-500  max-w-[200px] truncate group-hover:whitespace-normal group-hover:break-words transition-all duration-300 cursor-help" title="{{ $record->obs }}">
                                {{ $record->obs ?: '-' }}
                            </td>
                            
                            <td class="p-4 pr-6 align-top">
                                <div class="flex items-center justify-center gap-2 opacity-80 group-hover:opacity-100 transition-opacity">
                                    
                                    {{-- Imprimir (Laranja) --}}
                                    <a href="{{ route('agent.record.print', ['schedule' => $record->schedule_id, 'record' => $record->id]) }}"
                                       class="p-2 rounded-lg text-orange-500 hover:bg-orange-50  transition-colors"
                                       title="Imprimir Ficha">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                    </a>

                                    @if(Auth::user()->isAdmin())
                                        {{-- Excluir (Vermelho) --}}
                                        <form method="POST" id="form-delete-{{ $record->id }}" action="{{ route('agent.record.destroy', ['schedule' => $record->schedule_id, 'record' => $record->id]) }}" class="inline-block m-0 p-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" @click="confirmDelete('{{ $record->id }}')"
                                                    class="p-2 rounded-lg text-rose-500 hover:bg-rose-50  transition-colors"
                                                    title="Excluir Definitivamente">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </form>
                                    @endif

                                    @if(Auth::user()->email === 'cassianogf@gmail.com')
                                        {{-- Sync Master (Azul) --}}
                                        <button type="button" @click="syncData('{{ $record->id }}', $event.currentTarget)"
                                                class="p-2 rounded-lg text-indigo-500 hover:bg-indigo-50  transition-colors disabled:opacity-50"
                                                title="Forçar Sincronização via API">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 icon-sync" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="p-12 text-center text-gray-500 ">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="h-12 w-12 text-gray-300  mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    <span class="text-base font-semibold">Nenhuma Ficha/Registro de Atendimento</span>
                                    <span class="text-sm mt-1">Este agendamento ainda não possui horas trabalhadas computadas.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if(count($records) > 0)
            <div class="bg-gray-50  border-t border-gray-200  px-4 py-3 sm:px-6">
                <p class="text-sm text-gray-500  font-medium">
                    Total de <span class="text-gray-700 ">{{ count($records) }}</span> ficha(s) listadas.
                </p>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up {
        animation: fadeInUp 0.4s ease-out forwards;
    }
</style>
@endsection

@section('footer')
<script>
    document.addEventListener('alpine:init', () => {
        globalThis.RecordListActions = () => ({
            
            init() {
                // Config inicial disparada direto do alpine se precisar
            },

            async confirmDelete(id) {
                const ok = await window.confirmModal({
                    title: 'Excluir registro?',
                    message: 'O registro de suporte será excluído definitivamente. Esta ação não pode ser desfeita.',
                    confirmLabel: 'Excluir',
                });
                if (ok) {
                    const form = document.getElementById('form-delete-' + id);
                    if (form) form.submit();
                }
            },

            notifyToast(message, type = 'success') {
                const toastType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
                const method = globalThis.AppToast?.[toastType] || globalThis.AppToast?.show;

                if (typeof method === 'function') {
                    method({ message, type: toastType });
                    return;
                }

                globalThis.dispatchEvent(new CustomEvent('app-toast', {
                    detail: { message, type: toastType },
                }));
            },

            // Fetch Assíncrono para Sync (sem jQuery $.ajax) com feedback padronizado
            syncData(id, btnElement) {
                const apiBaseUrl = document.getElementById('js-api-config').dataset.syncUrl;
                
                // UX: gira botāo
                const icon = btnElement.querySelector('.icon-sync');
                if(icon) icon.classList.add('animate-spin');
                btnElement.disabled = true;

                fetch(`${apiBaseUrl}/${id}/sync`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(async response => {
                    const isJson = response.headers.get('content-type')?.includes('application/json');
                    const data = isJson ? await response.json() : null;

                    if (!response.ok) {
                        const errorMsg = (data && data.msg) ? data.msg : 'Erro na API ao sincronizar o Agendamento.';
                        throw new Error(errorMsg);
                    }
                    
                    this.notifyToast('Ficha sincronizada corretamente com o banco.', 'success');
                })
                .catch(err => {
                    this.notifyToast(err.message, 'error');
                })
                .finally(() => {
                    if(icon) icon.classList.remove('animate-spin');
                    btnElement.disabled = false;
                });
            }
        });
    });
</script>
@endsection
