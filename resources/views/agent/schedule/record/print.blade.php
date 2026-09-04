<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ordem de Serviço #{{ $record->id }} - Impressão</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/amura-icon.png') }}?v=2">
    
    {{-- Carrega o Tailwind CSS --}}
    @vite(['resources/css/app.css'])

    {{-- Estilos Auxiliares de Impressão --}}
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        @media print {
            body { 
                background: white !important; 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            .no-print { display: none !important; }
            .avoid-page-break { page-break-inside: avoid; }
            .print-border-black { border-color: #000 !important; }
            .print-text-black { color: #000 !important; }
        }
        /* Custom Tailwind overrides estritos pro papel P&B */
        .paper-border { border: 1px solid #000; }
        .paper-border-b { border-bottom: 1px solid #000; }
        .paper-border-r { border-right: 1px solid #000; }
        .paper-border-l { border-left: 1px solid #000; }
        .paper-border-t { border-top: 1px solid #000; }
        .paper-bg { background-color: #f1f5f9; }
    </style>
</head>
<body class="bg-gray-100 font-sans text-gray-900 antialiased min-h-screen print:bg-white flex justify-center py-8 print:py-0">
    
    {{-- Container A4 (Largura rígida Padrão A4 ~ 210mm simulada) --}}
    <div class="bg-white w-full max-w-[800px] shadow-2xl print:shadow-none p-8 print:p-0">
        
        {{-- Botão de Impressão Flutuante (Some ao imprimir) --}}
        <div class="no-print flex justify-end mb-6">
            <button onclick="window.print()" class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-md transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                Imprimir Documento
            </button>
        </div>

        <div id="document-content" class="w-full flex flex-col paper-border print-text-black print-border-black mx-auto">
            
            {{-- Cabeçalho Empresa --}}
            <div class="grid grid-cols-2 items-center p-4">
                <div class="flex justify-start">
                    <img src="{{ asset('img/amura-logo-light.png') }}" class="w-40 object-contain" alt="Amura Sistemas">
                </div>
                <div class="text-right text-[10px] leading-tight flex flex-col items-end">
                    <p class="font-bold text-xs mb-1 uppercase tracking-wide">Amura / EasyMaster Sistemas</p>
                    <p>CNPJ: 36.423.135/0001-07</p>
                    <p>Rua Fortunato Ramos, 248</p>
                    <p>Ed. Praia Trade Center — Sala 608</p>
                    <p>Santa Lúcia — Vitória — ES</p>
                    <p class="font-bold mt-1">Fone: (27) 3325-4451</p>
                </div>
            </div>

            {{-- Título OS --}}
            <div class="paper-border-t paper-border-b text-center py-1.5 paper-bg print:bg-black/5">
                <h1 class="text-[13px] font-bold uppercase tracking-widest m-0">Ordem de Serviço nº {{ $record->id }}</h1>
            </div>

            {{-- Grade: Detalhes da OS (Linha 1) --}}
            <div class="grid grid-cols-12 text-[11px] leading-none paper-border-b">
                <div class="col-span-2 p-1.5 paper-border-r">
                    <span class="font-semibold mr-1">Data:</span> {{ $record->start?->format('d/m/Y') }}
                </div>
                <div class="col-span-2 p-1.5 paper-border-r">
                    <span class="font-semibold mr-1">Versão:</span> {{ $record->version }}
                </div>
                <div class="col-span-2 p-1.5 paper-border-r">
                    <span class="font-semibold mr-1">Release:</span> {{ $record->release }}
                </div>
                <div class="col-span-3 p-1.5 paper-border-r">
                    <span class="font-semibold mr-1">Início:</span> {{ $record->start?->format('H:i') }}
                </div>
                <div class="col-span-3 p-1.5">
                    <span class="font-semibold mr-1">Fim:</span> {{ $record->end?->format('H:i') }}
                </div>
            </div>

            {{-- Grade: Detalhes da OS (Linha 2) --}}
            <div class="grid grid-cols-12 text-[11px] leading-none paper-border-b">
                <div class="col-span-2 p-1.5 paper-border-r flex items-center">
                    <span class="font-semibold mr-1">Possui NF-e:</span> ( <span class="px-1 text-transparent">X</span> )
                </div>
                <div class="col-span-4 p-1.5 paper-border-r flex items-center justify-between px-2">
                    <span class="font-semibold mr-1">Resolvido:</span> 
                    <span class="flex gap-4">
                        <span>( <span class="px-1 text-transparent">X</span> ) Sim</span>
                        <span>( <span class="px-1 text-transparent">X</span> ) Não</span>
                    </span>
                </div>
                <div class="col-span-3 p-1.5 paper-border-r">
                    <span class="font-semibold mr-1">Interv. De:</span> {{ $record->interval_start?->format('H:i') ?? '--:--' }}
                </div>
                <div class="col-span-3 p-1.5">
                    <span class="font-semibold mr-1">Até:</span> {{ $record->interval_end?->format('H:i') ?? '--:--' }}
                </div>
            </div>

            {{-- Título Cliente --}}
            <div class="text-center py-1 paper-bg print:bg-black/5">
                <h2 class="text-xs font-bold uppercase tracking-wider m-0">Dados do Cliente</h2>
            </div>

            {{-- Grade: Cliente --}}
            <div class="text-[11px] leading-snug paper-border-t paper-border-b">
                <div class="grid grid-cols-12 border-b border-black last:border-b-0">
                    <div class="col-span-2 p-1.5 font-bold paper-border-r">EMPRESA:</div>
                    <div class="col-span-10 p-1.5 px-2">{{ $customer?->trade_name }}</div>
                </div>
                <div class="grid grid-cols-12 border-b border-black last:border-b-0">
                    <div class="col-span-2 p-1.5 font-bold paper-border-r">ENDEREÇO:</div>
                    <div class="col-span-10 p-1.5 px-2">
                        {{ $customer?->logradouro }}, {{ $customer?->numero }}. {{ $customer?->bairro }}.
                        {{ $customer?->city }}. {{ $customer?->state?->abbr }}. CEP: {{ $customer?->postal_code }}
                    </div>
                </div>
                <div class="grid grid-cols-12 border-b border-black last:border-0">
                    <div class="col-span-2 p-1.5 font-bold paper-border-r">PONTO DE REF:</div>
                    <div class="col-span-10 p-1.5 px-2"></div>
                </div>
            </div>

            {{-- Título Módulo Checklist --}}
            <div class="text-center py-1 paper-bg print:bg-black/5">
                <h2 class="text-xs font-bold uppercase tracking-wider m-0">{{ Str::upper($record->module?->name) }} (CHECKLIST DA ATIVIDADE)</h2>
            </div>

            {{-- Checklists / Elementos Dinâmicos - Layout Tailwind Grid Dinâmico --}}
            <div class="p-2 paper-border-t paper-border-b min-h-[150px]">
                <div class="flex flex-col gap-3">
                    @forelse($elements as $key => $group)
                        <div class="avoid-page-break">
                            <h3 class="text-[11px] font-bold border-b border-dashed border-gray-400 pb-0.5 mb-1">{{ $key }}</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-x-2 gap-y-1">
                                @foreach($group as $element)
                                    @switch($element['type'])
                                        @case('checkbox')
                                            <div class="col-span-1 text-[10px] flex items-start gap-1">
                                                <span class="font-mono font-bold">{{ $element['value'] ? '(X)' : '( )' }}</span> 
                                                <span class="leading-tight">{{ $element['label'] }}</span>
                                            </div>
                                            @break
                                        @case('textarea')
                                            <div class="col-span-full border-b border-dotted border-gray-300 pb-1 mt-1 text-[11px]">
                                                <span class="font-bold">{{ $element['label'] }}:</span> 
                                                <span class="italic">{{ $element['value'] }}</span>
                                            </div>
                                            @break
                                        @default
                                            <div class="col-span-1 text-[10px]">
                                                <span class="font-bold">{{ $element['label'] }}:</span> 
                                                <span>{{ $element['value'] ?: '_____' }}</span>
                                            </div>
                                    @endswitch
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="text-xs text-center p-4 italic opacity-80">Nenhum detalhe adicional de módulo parametrizado.</div>
                    @endforelse
                </div>
            </div>

            {{-- Observações Livres --}}
            <div class="flex flex-col min-h-[120px]">
                <div class="text-center py-1 paper-bg print:bg-black/5 paper-border-b">
                    <h2 class="text-xs font-bold uppercase tracking-wider m-0">Observações Gerais / Parecer</h2>
                </div>
                <div class="p-3 text-[11px] leading-relaxed flex-grow">
                    {!! nl2br(e($record->obs ?: "Nenhuma observação informada.")) !!}
                </div>
            </div>
        </div>
        
        {{-- Rodapé / Assinaturas (Fora do bloco com borda pai) --}}
        <div class="w-full mt-8 grid grid-cols-12 gap-4 text-[12px] avoid-page-break print-text-black">
            
            <div class="col-span-5 text-center flex flex-col items-center justify-end">
                <div class="w-full border-b border-black mb-1"></div>
                <p class="font-semibold m-0 leading-none">{{ $record->agent?->name ?? 'Técnico Responsável' }}</p>
                <p class="text-[10px] uppercase text-gray-500 print-text-black m-0">Suporte Amura</p>
            </div>
            
            <div class="col-span-2 text-center flex items-end justify-center">
                <p class="m-0 mb-1">Data: ____/____/_____</p>
            </div>
            
            <div class="col-span-5 text-center flex flex-col items-center justify-end">
                <div class="w-full border-b border-black mb-1"></div>
                <p class="font-semibold m-0 leading-none">{{ $record->contact ?: 'Representante do Cliente' }}</p>
                <p class="text-[10px] uppercase text-gray-500 print-text-black m-0">Assinatura do Responsável</p>
            </div>

            <div class="col-span-12 mt-4 text-center">
                <p class="text-[9px] italic text-gray-600 print-text-black tracking-wide leading-relaxed px-10">
                    Declaro que os serviços e/ou visitas técnicas descritas nesta Ordem de Serviço foram prestados,
                    horas executadas conferidas, e as atividades dadas como averiguadas e aceitas por mim, nesta referida data.
                </p>
            </div>
            
        </div>
    </div>
</body>
</html>
