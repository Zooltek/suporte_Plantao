{{-- Componente: components/print-button.blade.php --}}
<div x-data="{ printing: false }" class="print:hidden">
    {{-- Botão de Impressão Refatorado Premium UI (Light & Dark Mode) --}}
    <button
        @click="printing = true; window.print(); setTimeout(() => printing = false, 500)"
        :disabled="printing"
        type="button"
        class="inline-flex items-center gap-2 px-6 py-2.5 bg-white  border border-gray-300  hover:bg-gray-50  text-gray-700  hover:text-indigo-600  rounded-xl font-bold text-sm transition-all shadow-sm hover:shadow-md active:scale-95 disabled:opacity-50 ring-1 ring-transparent focus:outline-none focus:ring-gray-300 "
        title="Imprimir ou Salvar como PDF (Ctrl+P)"
    >
        <template x-if="!printing">
            <svg class="w-5 h-5 text-indigo-500 " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9v4a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
        </template>
        
        <template x-if="printing">
            <svg class="animate-spin h-5 w-5 text-indigo-600 " fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </template>

        <span x-text="printing ? 'Preparando Módulo...' : '{{ $slot->isEmpty() ? 'Imprimir Relatório' : $slot }}'"></span>
    </button>
</div>

{{-- Estilos Globais de Impressão Otimizados --}}
@once
<style>
    @media print {
        /* Esconde Botões e UIs Desnecessárias */
        .print\:hidden { display: none !important; }

        /* Força fundo branco absoluto, cortando SVG/Gradientes vazados em PDFs */
        body {
            background-color: white !important;
            color: black !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Padroniza as margens do PDF via Sistema Operacional */
        @page { margin: 1.5cm; }

        /* Suprime o texto [href] bizarro ao lado dos links exportados */
        a[href]:after { content: none !important; }
    }
</style>
@endonce
