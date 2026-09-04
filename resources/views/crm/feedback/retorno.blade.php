@extends('crm.layouts.master-blank')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-slate-900 py-8 px-4 sm:px-6 lg:px-8 transition-colors duration-300">
    <div class="max-w-4xl mx-auto bg-white dark:bg-slate-800 rounded-xl shadow-md overflow-hidden p-6 border border-gray-200 dark:border-slate-700 transition-colors duration-300">
        
        <form
            method="POST"
            id="feedback-return-form"
            action="{{ route('feedback.retorno', ['id' => $feedback->id]) }}"
        >
            @csrf
            <input type="hidden" name="feedback_id" value="{{ $feedback->id }}">

            {{-- CABEÇALHO DO CLIENTE --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <label for="customer_name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        Cliente
                    </label>
                    <input
                        type="text"
                        id="customer_name"
                        class="block w-full rounded-md border-gray-300 dark:border-slate-600 bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-gray-400 shadow-sm sm:text-sm cursor-not-allowed focus:ring-0 focus:border-gray-300"
                        value="{{ $feedback->customer->trade_name }}"
                        readonly
                    >
                </div>

                <div>
                    <label for="contact_name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        Contato
                    </label>
                    <input
                        type="text"
                        id="contact_name"
                        class="block w-full rounded-md border-gray-300 dark:border-slate-600 bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-gray-400 shadow-sm sm:text-sm cursor-not-allowed focus:ring-0 focus:border-gray-300"
                        value="{{ $feedback->customer->contact_1_name }}"
                        readonly
                    >
                </div>
            </div>

            {{-- HISTÓRICO (Carregado via Fetch) --}}
            <div class="mb-8 border-t border-b border-gray-100 dark:border-slate-700 py-6">
                <h3 class="text-sm font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-4">Histórico Recente</h3>
                
                {{-- Container onde o HTML do histórico será injetado --}}
                <div id="resultado-historico" class="min-h-[100px] text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex justify-center items-center py-4">
                        <i class="fa-solid fa-circle-notch fa-spin text-brand-500 text-2xl"></i>
                        <span class="ml-2 text-gray-500">Carregando histórico...</span>
                    </div>
                </div>
            </div>

            {{-- DETALHES DO FEEDBACK --}}
            <div class="bg-blue-50/50 dark:bg-slate-700/30 rounded-lg p-6 border border-blue-100 dark:border-slate-600 mb-8 space-y-3">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200 border-b border-blue-100 dark:border-slate-600 pb-2 mb-4">
                    Detalhes do Feedback
                </h3>

                <p class="text-sm">
                    <strong class="text-gray-700 dark:text-gray-300">Data:</strong>
                    <span class="text-gray-900 dark:text-gray-100">{{ optional($feedback->completed_at)->format('d/m/Y H:i') }}</span>
                </p>

                {{-- Elementos Dinâmicos --}}
                @foreach($feedback->elements as $element)
                    <p class="text-sm">
                        <strong class="text-gray-700 dark:text-gray-300">{{ $element->type->label }}:</strong>
                        <span class="font-medium text-gray-900 dark:text-gray-100">
                            {{ $feedback->getNamedValue($element->value, $element->type) }}
                        </span>
                    </p>
                @endforeach

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    @if($feedback->complaint)
                    <div class="bg-white dark:bg-slate-800 p-3 rounded border border-gray-200 dark:border-slate-600 shadow-sm">
                        <strong class="text-red-600 dark:text-red-400 block text-xs uppercase mb-1">Reclamações</strong>
                        <p class="text-gray-800 dark:text-gray-200 text-sm">{{ $feedback->complaint }}</p>
                    </div>
                    @endif

                    @if($feedback->suggestions)
                    <div class="bg-white dark:bg-slate-800 p-3 rounded border border-gray-200 dark:border-slate-600 shadow-sm">
                        <strong class="text-blue-600 dark:text-blue-400 block text-xs uppercase mb-1">Sugestões</strong>
                        <p class="text-gray-800 dark:text-gray-200 text-sm">{{ $feedback->suggestions }}</p>
                    </div>
                    @endif
                </div>

                @if($feedback->content)
                <div class="mt-2">
                    <strong class="text-gray-700 dark:text-gray-300 block text-sm mb-1">Observações Gerais:</strong>
                    <p class="text-gray-600 dark:text-gray-400 text-sm italic bg-white dark:bg-slate-800 p-2 rounded border border-gray-200 dark:border-slate-600">
                        "{{ $feedback->content }}"
                    </p>
                </div>
                @endif
            </div>

            {{-- ÁREA DE AVALIAÇÃO / RESPOSTA --}}
            <div class="mb-6">
                <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Avaliar o feedback</h4>
                
                <label for="return_content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Sua Observação / Retorno
                </label>
                <textarea
                    id="return_content"
                    name="return"
                    rows="4"
                    class="block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                    placeholder="Digite aqui o retorno sobre este feedback..."
                >{{ old('return', $feedback->return_content) }}</textarea>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-slate-700" x-data="{ isSubmitting: false }" @submit.prevent="isSubmitting = true; $el.closest('form').submit()">
                <button
                    type="submit"
                    :disabled="isSubmitting"
                    :class="isSubmitting ? 'opacity-70 cursor-not-allowed' : 'hover:bg-blue-700'"
                    class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                >
                    <i x-show="!isSubmitting" class="fa-solid fa-check mr-2"></i>
                    <svg x-show="isSubmitting" x-cloak class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="isSubmitting ? 'Salvando...' : 'Salvar'">Salvar</span>
                </button>
            </div>

        </form>
    </div>
</div>
@endsection

@section('footer')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        loadHistory();
    });

    
    function loadHistory() {
        const url = @json(route('feedback.customer.history', ['id' => $feedback->customer->id]));
        const container = document.getElementById('resultado-historico');

        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error('Erro na rede');
                return response.text();
            })
            .then(html => {
                // Injeta o HTML retornado na div
                container.innerHTML = html;
            })
            .catch(error => {
                console.error('Erro ao carregar histórico:', error);
                container.innerHTML = `
                    <div class="bg-red-50 text-red-600 p-3 rounded text-sm text-center">
                        Não foi possível carregar o histórico.
                    </div>
                `;
            });
    }

    // Mantém a lógica de atualizar a janela pai ao fechar/sair
    window.onunload = function () {
        if (window.opener && typeof window.opener.refresh === 'function') {
            try {
                window.opener.refresh();
            } catch (e) {
                console.log('Janela pai já fechada ou inacessível');
            }
        }
    };
</script>
@endsection