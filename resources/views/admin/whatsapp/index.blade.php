@extends('admin.layouts.master')

@section('page-title', 'WhatsApp — Configuração e Monitoramento')

@section('content')
<div
    class="max-w-5xl mx-auto px-4 py-8 space-y-8"
    x-data="whatsappAdminPanel({
        enabled: {{ $config['enabled'] ? 'true' : 'false' }},
        provider: '{{ $config['provider'] }}',
        urls: {
            connectionState: '{{ route('admin.whatsapp.connection-state', [], false) }}',
            logout: '{{ route('admin.whatsapp.logout', [], false) }}',
            qrCode: '{{ route('admin.whatsapp.qr-code', [], false) }}',
        },
    })"
    x-init="init()"
>

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <div class="p-2 rounded-xl bg-green-100">
            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.127.558 4.121 1.532 5.854L.057 23.882l6.186-1.454A11.934 11.934 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.894 0-3.668-.523-5.183-1.432l-.371-.22-3.676.864.923-3.577-.241-.388A9.958 9.958 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
            </svg>
        </div>
        <div>
            <h1 class="text-xl font-bold text-gray-900">WhatsApp Business</h1>
            <p class="text-sm text-gray-500">Configuração da integração e monitoramento de conversas</p>
        </div>
        <div class="ml-auto">
            <span
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                :class="headerBadgeClasses()"
            >
                <span class="w-1.5 h-1.5 rounded-full" :class="headerDotClasses()"></span>
                <span x-text="headerStatusLabel()"></span>
            </span>
        </div>
    </div>

    {{-- 1. Configuração Técnica: Expandido e Compactado --}}
    <div x-data="{ open: true, copiedEnv: false }" class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm transition">
        {{-- Card Header com botão de alternância --}}
        <div class="px-5 py-3.5 bg-gray-50 flex items-center justify-between border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="p-1.5 rounded-lg bg-gray-200 text-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-800">Configuração Técnica</h2>
                    <p class="text-xs text-gray-500">Parâmetros de conexão, Webhook, URLs e variáveis .env</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @if(config('whatsapp.provider') === 'evolution')
                <span
                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold"
                    :class="instanceBadgeClasses()"
                >
                    <span class="w-1.5 h-1.5 rounded-full" :class="instanceDotClasses()"></span>
                    <span x-text="instanceStatusLabel()"></span>
                </span>
                @endif
                <button
                    type="button"
                    @click="open = !open"
                    class="flex items-center gap-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 transition focus:outline-none"
                    :aria-expanded="open.toString()"
                >
                    <span x-text="open ? 'Recolher' : 'Expandir'"></span>
                    <svg
                        class="w-4 h-4 text-gray-400 transform transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
        </div>

        <div x-show="open" x-cloak class="divide-y divide-gray-100">
            {{-- Conexão da Instância Evolution (Compacto) --}}
            @if(config('whatsapp.provider') === 'evolution')
            <div class="px-5 py-4 bg-gray-50/50">
                {{-- Conectado --}}
                <div x-show="state === 'open'" class="flex flex-wrap items-center justify-between gap-3 bg-white p-3 rounded-lg border border-gray-200">
                    <div class="flex items-center gap-3 min-w-0">
                        <div
                            class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                            :class="configEnabled ? 'bg-green-100 text-green-600' : 'bg-amber-100 text-amber-600'"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="text-xs">
                            @if($config['enabled'])
                                <span class="font-semibold text-gray-800">WhatsApp conectado com sucesso!</span>
                                <span class="text-gray-500 block sm:inline sm:ml-1">O bot está ativo e pronto para receber mensagens.</span>
                            @else
                                <span class="font-semibold text-gray-800">WhatsApp conectado, mas integração desabilitada.</span>
                                <span class="text-gray-500 block sm:inline sm:ml-1">As mensagens são registradas no sistema, porém o envio automático ao provedor está desligado.</span>
                            @endif
                            <span class="text-gray-400 ml-1">· Instância: <span class="font-mono font-medium text-gray-700">{{ config('whatsapp.evolution_instance') }}</span></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 ml-auto">
                        <button @click="checkState()" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Atualizar status
                        </button>
                        <button @click="logoutInstance()" :disabled="loading" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50 rounded border border-red-200 transition disabled:opacity-50">
                            Resetar Sessão
                        </button>
                    </div>
                </div>

                {{-- Desconectado / Aguardando QR Code --}}
                <div x-show="state !== 'open'" class="p-4 bg-white rounded-lg border border-gray-200 space-y-4">
                    <div class="flex flex-col md:flex-row items-center gap-6">
                        {{-- Área do QR Code --}}
                        <div class="flex flex-col items-center gap-2 flex-shrink-0">
                            {{-- Loading --}}
                            <div x-show="loading" class="w-44 h-44 flex items-center justify-center border-2 border-dashed border-gray-200 rounded-xl">
                                <svg class="animate-spin w-8 h-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </div>

                            {{-- QR Image --}}
                            <div x-show="qrBase64 && !loading" class="flex flex-col items-center gap-1">
                                <img :src="qrBase64" alt="QR Code WhatsApp" class="w-44 h-44 rounded-xl border-2 border-gray-200 shadow-sm">
                                <p class="text-[11px] text-gray-400">Expira em ~20s.</p>
                            </div>

                            {{-- Placeholder --}}
                            <div x-show="!qrBase64 && !loading" class="w-44 h-44 flex flex-col items-center justify-center border-2 border-dashed border-gray-200 rounded-xl text-gray-400 gap-1 p-2 text-center">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m0 14v1M4 12h1m14 0h1m-2.636-7.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707M17.657 17.657l.707.707"/>
                                </svg>
                                <span class="text-xs">Aguardando geração</span>
                            </div>
                        </div>

                        {{-- Instruções e Ações --}}
                        <div class="space-y-3 flex-1">
                            <p class="text-xs text-gray-600">
                                Para autenticar o WhatsApp, escaneie o QR Code com o celular vinculado ao número
                                <strong>{{ config('whatsapp.from_number') ? '+'.config('whatsapp.from_number') : '—' }}</strong>.
                            </p>

                            <div class="bg-gray-50 rounded-lg p-3 text-xs text-gray-600 space-y-1">
                                <p class="font-semibold text-gray-700">Passo a passo:</p>
                                <p>1. Abra o WhatsApp no celular → <strong>Configurações / Aparelhos conectados</strong>.</p>
                                <p>2. Toque em <strong>Conectar aparelho</strong> e escaneie o código.</p>
                            </div>

                            {{-- Aviso operacional --}}
                            <div x-show="infoMsg" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs">
                                <p class="font-semibold text-amber-900" x-text="infoTitle"></p>
                                <p x-text="infoMsg" class="mt-0.5 text-amber-800 leading-relaxed"></p>
                            </div>

                            <p x-show="errorMsg" x-text="errorMsg" class="text-xs text-red-600"></p>

                            <div class="flex items-center gap-2 pt-1 flex-wrap">
                                <button @click="generateQr()" :disabled="loading" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-green-600 text-white hover:bg-green-700 disabled:opacity-50 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 3h2m0 0h2m-2 0v2m0-2v-2"/>
                                    </svg>
                                    <span x-text="qrBase64 ? 'Novo QR Code' : 'Gerar QR Code'"></span>
                                </button>
                                <button @click="checkState()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                                    Verificar status
                                </button>
                                <button @click="logoutInstance()" :disabled="loading" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 transition disabled:opacity-50">
                                    Resetar Sessão
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Parâmetros e Variáveis (.env) Compactados --}}
            <div class="p-5 grid grid-cols-1 lg:grid-cols-12 gap-5">
                {{-- Coluna 1: Parâmetros do Sistema (7 cols) --}}
                <div class="lg:col-span-7 space-y-2">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Parâmetros de Conexão</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach([
                            ['key' => 'Integração',     'value' => $config['enabled'] ? 'Habilitada' : 'Desabilitada', 'color' => $config['enabled'] ? 'text-emerald-700 bg-emerald-50' : 'text-gray-600 bg-gray-50'],
                            ['key' => 'Provedor',       'value' => ucfirst($config['provider']), 'color' => 'text-indigo-700 bg-indigo-50'],
                            ['key' => 'Número Oficial', 'value' => '+' . $config['from_number'], 'dusk' => 'whatsapp-official-number', 'mono' => true],
                            [
                                'key' => 'Números Locais',
                                'value' => collect($config['local_test_numbers'] ?? [])
                                    ->map(fn (string $number) => '+' . $number)
                                    ->implode(', '),
                                'dusk' => 'whatsapp-local-test-numbers',
                                'mono' => true,
                            ],
                            ['key' => 'API URL',        'value' => $config['api_url']],
                            ['key' => 'API Token',      'value' => $config['api_token']],
                        ] as $item)
                        <div class="p-2.5 rounded-lg border border-gray-100 bg-gray-50/50 flex flex-col justify-between">
                            <span class="text-[11px] text-gray-500 font-medium">{{ $item['key'] }}</span>
                            <span
                                class="text-xs font-medium mt-0.5 truncate @if(!empty($item['mono'])) font-mono text-gray-800 @endif @if(!empty($item['color'])) inline-block px-1.5 py-0.5 rounded text-[11px] font-semibold w-fit {{ $item['color'] }} @else text-gray-800 @endif"
                                @if(!empty($item['dusk'])) dusk="{{ $item['dusk'] }}" @endif
                                title="{{ $item['value'] !== '' ? $item['value'] : '—' }}"
                            >{{ $item['value'] !== '' ? $item['value'] : '—' }}</span>
                        </div>
                        @endforeach
                    </div>

                    {{-- Webhook URL em largura completa --}}
                    <div class="p-2.5 rounded-lg border border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <span class="text-[11px] text-gray-500 font-medium whitespace-nowrap">Webhook URL</span>
                        <span class="text-xs font-mono text-gray-800 truncate bg-white px-2 py-0.5 rounded border border-gray-200" title="{{ $config['webhook_url'] }}">
                            {{ $config['webhook_url'] }}
                        </span>
                    </div>
                </div>

                {{-- Coluna 2: Variáveis de Ambiente (.env) Compacto (5 cols) --}}
                <div class="lg:col-span-5 flex flex-col">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xs font-semibold text-amber-800 uppercase tracking-wider">Variáveis (.env)</h3>
                        <button
                            type="button"
                            @click="navigator.clipboard.writeText($refs.envContent.innerText); copiedEnv = true; setTimeout(() => copiedEnv = false, 2000)"
                            class="text-[11px] text-amber-700 hover:text-amber-900 font-medium flex items-center gap-1"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span x-text="copiedEnv ? 'Copiado!' : 'Copiar .env'"></span>
                        </button>
                    </div>
                    <div class="flex-1 bg-amber-50/80 border border-amber-200/80 rounded-lg p-3 overflow-hidden flex flex-col">
                        <pre x-ref="envContent" class="text-[11px] text-amber-950 overflow-x-auto overflow-y-auto max-h-52 leading-relaxed font-mono flex-1">WHATSAPP_ENABLED=true
WHATSAPP_PROVIDER=evolution
WHATSAPP_FROM_NUMBER=27981180125
WHATSAPP_LOCAL_TEST_NUMBERS=27981180125,45999178290
WHATSAPP_API_URL=https://evolution.seu-dominio.com.br
WHATSAPP_WEBHOOK_URL=${APP_URL}/api/webhook/whatsapp
WHATSAPP_EVOLUTION_INSTANCE=amura-prod
WHATSAPP_EVOLUTION_API_KEY=defina_uma_chave_forte_aqui
WHATSAPP_DEFAULT_AGENT_ID=   # ID do agente padrão de triagem
WHATSAPP_AUTO_IDENTIFY_COMPANY_BY_PHONE=true
WHATSAPP_ORIGIN_ID=5
WHATSAPP_DEFAULT_STATUS_ID=1
WHATSAPP_DEFAULT_PRIORITY_ID=1
WHATSAPP_CATEGORY_SUPORTE=1  # ID da categoria Suporte Técnico
WHATSAPP_CATEGORY_FINANCEIRO=1
WHATSAPP_CATEGORY_COMERCIAL=1</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Expediente WhatsApp / Atalhos de Mensagem --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-700">Expediente WhatsApp</h2>
            </div>
            <form method="POST" action="{{ route('admin.whatsapp.settings.update') }}" class="p-5 grid grid-cols-2 gap-4">
                @csrf
                <label class="text-xs font-semibold text-gray-600">
                    Início
                    <input type="time" name="business_hours_start" value="{{ $whatsappSettings['business_hours_start'] }}"
                           class="mt-1 w-full rounded-lg border-gray-200 text-sm">
                </label>
                <label class="text-xs font-semibold text-gray-600">
                    Fim
                    <input type="time" name="business_hours_end" value="{{ $whatsappSettings['business_hours_end'] }}"
                           class="mt-1 w-full rounded-lg border-gray-200 text-sm">
                </label>
                <label class="text-xs font-semibold text-gray-600">
                    Dias úteis
                    <input type="text" name="business_days" value="{{ $whatsappSettings['business_days'] }}"
                           class="mt-1 w-full rounded-lg border-gray-200 text-sm" placeholder="1,2,3,4,5">
                </label>
                <label class="text-xs font-semibold text-gray-600">
                    Cooldown fora expediente
                    <input type="number" name="out_of_hours_cooldown_minutes" value="{{ $whatsappSettings['out_of_hours_cooldown_minutes'] }}"
                           class="mt-1 w-full rounded-lg border-gray-200 text-sm" min="15" max="1440">
                </label>
                <label class="text-xs font-semibold text-gray-600 col-span-2">
                    Delay no bot pós-finalização de chamado (minutos)
                    <input type="number" name="ticket_closed_delay_minutes" value="{{ $whatsappSettings['ticket_closed_delay_minutes'] }}"
                           class="mt-1 w-full rounded-lg border-gray-200 text-sm" min="0" max="1440" required>
                    <span class="text-[11px] text-gray-400 font-normal block mt-1">Tempo de espera após o encerramento do chamado. Mensagens do cliente dentro desse período (ex.: "Obrigado", "Valeu", "👍") são registradas no chamado, mas NÃO ativam o bot novamente. (0 = liberação imediata)</span>
                </label>
                <div class="col-span-2 flex justify-end">
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                        Salvar configurações
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                <h2 class="text-sm font-semibold text-gray-700">Atalhos de Mensagem</h2>
            </div>
            <form method="POST" action="{{ route('admin.whatsapp.macros.save') }}" class="p-5 space-y-3 border-b border-gray-100">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <input name="command" placeholder="/sefaz" class="rounded-lg border-gray-200 text-sm" required>
                    <select name="department_id" class="rounded-lg border-gray-200 text-sm">
                        <option value="">Todos os setores</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-600">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300">
                        Ativo
                    </label>
                </div>
                <textarea name="text" rows="2" class="w-full rounded-lg border-gray-200 text-sm" required>Sefaz fora do ar favor aguardar.</textarea>
                <div class="flex justify-end">
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                        Salvar atalho
                    </button>
                </div>
            </form>
            <div class="divide-y divide-gray-100">
                @forelse($macros as $macro)
                    <div class="px-5 py-3 flex items-start justify-between gap-3">
                        <div>
                            <p class="font-mono text-sm font-semibold text-gray-800">{{ $macro->command }}</p>
                            <p class="text-xs text-gray-500">{{ Str::limit($macro->text, 100) }}</p>
                            <p class="text-[11px] text-gray-400">{{ $macro->department?->name ?? 'Todos os setores' }} · {{ $macro->is_active ? 'Ativo' : 'Inativo' }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.whatsapp.macros.delete', $macro) }}">
                            @csrf @method('DELETE')
                            <button class="text-xs font-semibold text-red-600 hover:underline">Excluir</button>
                        </form>
                    </div>
                @empty
                    <div class="px-5 py-6 text-center text-sm text-gray-400">Nenhum atalho cadastrado.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 3. Conversas Recentes --}}
    <div class="space-y-4">
        {{-- Totalizadores de Atendimento --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'Total de Conversas', 'value' => $stats['total_conversations'], 'color' => 'blue'],
                ['label' => 'Em Andamento',        'value' => $stats['active'],              'color' => 'yellow'],
                ['label' => 'Concluídas',           'value' => $stats['completed'],           'color' => 'green'],
                ['label' => 'Tickets Gerados',      'value' => $stats['tickets_created'],     'color' => 'indigo'],
            ] as $stat)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs text-gray-500 font-medium">{{ $stat['label'] }}</p>
                <p class="text-3xl font-bold text-{{ $stat['color'] }}-600 mt-1">{{ $stat['value'] }}</p>
            </div>
            @endforeach
        </div>

        @php
            $initialConversations = $recentConversations->map(fn ($conv) => [
                'id' => $conv->id,
                'phone' => $conv->phone,
                'state' => $conv->state->value,
                'state_label' => $conv->state->label(),
                'ticket_id' => $conv->ticket_id,
                'messages_count' => $conv->messages_count ?? $conv->messages()->count(),
                'last_activity_at' => $conv->last_activity_at?->toIso8601String(),
                'last_activity_human' => $conv->last_activity_at?->diffForHumans(),
                'show_url' => route('admin.whatsapp.show', $conv->id),
                'ticket_url' => $conv->ticket_id ? route('agent.ticket.show', $conv->ticket_id) : null,
            ])->values();
        @endphp

        <div
            class="bg-white rounded-xl border border-gray-200 overflow-hidden"
            x-data="whatsappRecentConversations({
                initial: {{ $initialConversations->toJson() }},
                url: '{{ route('admin.whatsapp.conversations.recent') }}',
                intervalMs: 15000,
            })"
            x-init="init()"
        >
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-gray-700">Conversas Recentes</h2>
                    <p class="text-xs text-gray-400 mt-0.5" x-show="lastUpdatedLabel" x-text="'Atualizado ' + lastUpdatedLabel"></p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="polling ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-500'"
                          x-show="polling">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        Sincronizando
                    </span>
                    <button type="button"
                            @click="refresh(true)"
                            :disabled="loading"
                            class="text-xs text-indigo-600 hover:underline font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                        Atualizar agora
                    </button>
                </div>
            </div>

            <template x-if="conversations.length === 0">
                <div class="px-5 py-10 text-center text-sm text-gray-400">
                    Nenhuma conversa registrada ainda.
                </div>
            </template>

            <template x-if="conversations.length > 0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 tracking-wider">
                            <tr>
                                <th class="px-5 py-3 text-left">Telefone</th>
                                <th class="px-5 py-3 text-left">Estado</th>
                                <th class="px-5 py-3 text-left">Ticket</th>
                                <th class="px-5 py-3 text-left">Mensagens</th>
                                <th class="px-5 py-3 text-left">Última Atividade</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="conv in conversations" :key="conv.id">
                                <tr class="hover:bg-gray-50 cursor-pointer"
                                    :class="newlyArrived.has(conv.id) ? 'bg-amber-50/60 animate-pulse' : ''"
                                    @click="window.location = conv.show_url">
                                    <td class="px-5 py-3 font-mono text-gray-700" x-text="'+' + conv.phone"></td>
                                    <td class="px-5 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                              :class="stateColor(conv.state)"
                                              x-text="conv.state_label"></span>
                                    </td>
                                    <td class="px-5 py-3">
                                        <template x-if="conv.ticket_id">
                                            <a :href="conv.ticket_url"
                                               class="text-blue-600 hover:underline font-medium"
                                               target="_blank"
                                               x-text="'#' + conv.ticket_id"
                                               @click.stop></a>
                                        </template>
                                        <template x-if="!conv.ticket_id">
                                            <span class="text-gray-400">—</span>
                                        </template>
                                    </td>
                                    <td class="px-5 py-3 text-gray-500 tabular-nums" x-text="conv.messages_count"></td>
                                    <td class="px-5 py-3 text-gray-500" x-text="conv.last_activity_human ?? '—'"></td>
                                    <td class="px-5 py-3 text-right">
                                        <a :href="conv.show_url"
                                           :dusk="'conversation-link-' + conv.phone"
                                           class="text-xs text-indigo-600 hover:underline font-medium"
                                           @click.stop>
                                            Ver chat →
                                        </a>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </div>

    {{-- 4. Chamados Abertos via WhatsApp --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-700">Chamados Abertos via WhatsApp</h2>
                <p class="text-xs text-gray-400 mt-0.5">Origem = WhatsApp — últimos 50</p>
            </div>
            <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full">
                {{ $whatsappTickets->count() }}
            </span>
        </div>

        @if($whatsappTickets->isEmpty())
            <div class="px-5 py-10 text-center text-sm text-gray-400">
                Nenhum chamado gerado via WhatsApp ainda.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 tracking-wider">
                        <tr>
                            <th class="px-5 py-3 text-left">#</th>
                            <th class="px-5 py-3 text-left">Solicitante</th>
                            <th class="px-5 py-3 text-left">Empresa</th>
                            <th class="px-5 py-3 text-left">Assunto</th>
                            <th class="px-5 py-3 text-left">Status</th>
                            <th class="px-5 py-3 text-left">Prioridade</th>
                            <th class="px-5 py-3 text-left">Agente</th>
                            <th class="px-5 py-3 text-left">Aberto em</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($whatsappTickets as $ticket)
                        @php
                            $ticketStatus = $statusMap[$ticket->status_id] ?? null;
                            $statusColor  = match(true) {
                                str_contains(strtolower($ticketStatus?->name ?? ''), 'aberto')      => 'bg-blue-100 text-blue-700',
                                str_contains(strtolower($ticketStatus?->name ?? ''), 'atendimento') => 'bg-yellow-100 text-yellow-700',
                                str_contains(strtolower($ticketStatus?->name ?? ''), 'resolvido')   => 'bg-green-100 text-green-700',
                                str_contains(strtolower($ticketStatus?->name ?? ''), 'fechado')     => 'bg-gray-100 text-gray-500',
                                default => 'bg-gray-100 text-gray-500',
                            };
                            $priorityColor = match(strtolower($ticket->priority?->name ?? '')) {
                                'crítica', 'critica' => 'text-red-600 font-semibold',
                                'alta'               => 'text-orange-600',
                                'média', 'media'     => 'text-yellow-600',
                                default              => 'text-gray-500',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 cursor-pointer"
                            onclick="window.open('{{ route('agent.ticket.show', $ticket->id) }}', '_blank')">
                            <td class="px-5 py-3 font-mono text-gray-500 text-xs">#{{ $ticket->id }}</td>
                            <td class="px-5 py-3 font-medium text-gray-800">
                                {{ $ticket->contact ?: '—' }}
                                @if($ticket->obs && str_contains($ticket->obs, 'WhatsApp'))
                                    @php preg_match('/Número: (\S+)/', $ticket->obs, $m); @endphp
                                    @if(!empty($m[1]))
                                        <p class="text-xs text-gray-400 font-mono">+{{ $m[1] }}</p>
                                    @endif
                                @endif
                            </td>
                            <td class="px-5 py-3 text-gray-600 max-w-[140px] truncate">
                                {{ $ticket->company?->trade_name ?? $ticket->company?->name ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-gray-700 max-w-[200px]">
                                <span class="truncate block" title="{{ $ticket->subject }}">
                                    {{ Str::limit($ticket->subject, 45) }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                @if($ticketStatus)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                        {{ $ticketStatus->name }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-xs {{ $priorityColor }}">
                                {{ $ticket->priority?->name ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-gray-600 text-xs">
                                {{ $ticket->agent?->name ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-gray-500 text-xs whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($ticket->created_at)->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('agent.ticket.show', $ticket->id) }}"
                                   target="_blank"
                                   class="text-xs text-indigo-600 hover:underline font-medium"
                                   onclick="event.stopPropagation()">
                                    Ver →
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- 5. Mensagens do Bot --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-700">Mensagens do Bot</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($botMessages as $botMessage)
                <form method="POST" action="{{ route('admin.whatsapp.bot-messages.save') }}" class="p-5 grid grid-cols-1 lg:grid-cols-[160px_180px_1fr_auto] gap-3 items-start">
                    @csrf
                    <input type="hidden" name="key" value="{{ $botMessage['key'] }}">
                    <input name="step" value="{{ $botMessage['step'] }}" class="rounded-lg border-gray-200 text-sm">
                    <div>
                        <p class="font-mono text-xs font-semibold text-gray-700">{{ $botMessage['key'] }}</p>
                        <label class="mt-2 inline-flex items-center gap-2 text-xs text-gray-500">
                            <input type="checkbox" name="is_active" value="1" @checked($botMessage['is_active']) class="rounded border-gray-300">
                            Ativa
                        </label>
                    </div>
                    <textarea name="text" rows="2" class="w-full rounded-lg border-gray-200 text-sm">{{ $botMessage['text'] }}</textarea>
                    <button class="rounded-lg bg-gray-900 px-4 py-2 text-xs font-semibold text-white hover:bg-gray-800">
                        Salvar
                    </button>
                </form>
            @endforeach
        </div>
    </div>

    {{-- Scripts do Painel --}}
    @if(config('whatsapp.provider') === 'evolution')
    <script>
    function whatsappAdminPanel(config) {
        return {
            configEnabled: config.enabled,
            provider: config.provider,
            urls: config.urls,
            state:     'unknown',
            qrBase64:  '',
            loading:   false,
            infoMsg:   '',
            infoTitle: 'Aguardando QR Code',
            errorMsg:  '',
            pollTimer: null,

            init() {
                if (this.provider !== 'evolution') {
                    this.state = this.configEnabled ? 'open' : 'close';
                    return;
                }

                this.checkState();
            },

            isPendingState() {
                return ['connecting', 'initializing', 'booting'].includes(this.state);
            },

            headerStatusKey() {
                if (this.provider !== 'evolution') {
                    return this.configEnabled ? 'active' : 'disabled';
                }

                if (this.state === 'open') {
                    return this.configEnabled ? 'active' : 'connected_disabled';
                }

                if (this.isPendingState()) {
                    return 'connecting';
                }

                if (this.state === 'close') {
                    return this.configEnabled ? 'disconnected' : 'disabled';
                }

                if (this.state === 'not_configured') {
                    return 'not_configured';
                }

                return 'checking';
            },

            headerStatusLabel() {
                return {
                    active: 'Ativo',
                    connected_disabled: 'Conectado sem envio',
                    connecting: 'Aguardando conexão',
                    disconnected: 'Desconectado',
                    disabled: 'Desabilitado',
                    not_configured: 'Não configurado',
                    checking: 'Verificando...',
                }[this.headerStatusKey()];
            },

            headerBadgeClasses() {
                return {
                    active: 'bg-green-100 text-green-700',
                    connected_disabled: 'bg-amber-100 text-amber-800',
                    connecting: 'bg-yellow-100 text-yellow-700',
                    disconnected: 'bg-red-100 text-red-700',
                    disabled: 'bg-gray-100 text-gray-500',
                    not_configured: 'bg-gray-100 text-gray-500',
                    checking: 'bg-gray-100 text-gray-500',
                }[this.headerStatusKey()];
            },

            headerDotClasses() {
                return {
                    active: 'bg-green-500 animate-pulse',
                    connected_disabled: 'bg-amber-500',
                    connecting: 'bg-yellow-500 animate-pulse',
                    disconnected: 'bg-red-500',
                    disabled: 'bg-gray-400',
                    not_configured: 'bg-gray-400',
                    checking: 'bg-gray-400 animate-pulse',
                }[this.headerStatusKey()];
            },

            instanceStatusKey() {
                if (this.state === 'open') {
                    return this.configEnabled ? 'open' : 'open_disabled';
                }

                if (this.isPendingState()) {
                    return 'connecting';
                }

                if (this.state === 'close') {
                    return 'close';
                }

                if (this.state === 'not_configured') {
                    return 'not_configured';
                }

                return 'unknown';
            },

            instanceStatusLabel() {
                return {
                    open: 'Conectado',
                    open_disabled: 'Conectado sem envio',
                    connecting: this.state === 'booting' ? 'Inicializando serviço' : 'Aguardando QR Code',
                    close: 'Desconectado',
                    not_configured: 'Não configurado',
                    unknown: 'Verificando...',
                }[this.instanceStatusKey()];
            },

            instanceBadgeClasses() {
                return {
                    open: 'bg-green-100 text-green-700',
                    open_disabled: 'bg-amber-100 text-amber-800',
                    connecting: 'bg-yellow-100 text-yellow-700',
                    close: 'bg-red-100 text-red-700',
                    not_configured: 'bg-gray-100 text-gray-500',
                    unknown: 'bg-gray-100 text-gray-500',
                }[this.instanceStatusKey()];
            },

            instanceDotClasses() {
                return {
                    open: 'bg-green-500 animate-pulse',
                    open_disabled: 'bg-amber-500',
                    connecting: 'bg-yellow-500 animate-pulse',
                    close: 'bg-red-500',
                    not_configured: 'bg-gray-400',
                    unknown: 'bg-gray-400 animate-pulse',
                }[this.instanceStatusKey()];
            },

            clearPollTimer() {
                clearTimeout(this.pollTimer);
                this.pollTimer = null;
            },

            scheduleQrRetry(delay = 10000) {
                this.clearPollTimer();
                this.pollTimer = setTimeout(() => this.generateQr(true), delay);
            },

            scheduleStateRefresh(delay = 3000, maxDelay = 10000) {
                this.clearPollTimer();
                this.pollTimer = setTimeout(async () => {
                    await this.checkState();

                    if (this.state !== 'open') {
                        const next = Math.min(delay * 1.5, maxDelay);
                        this.scheduleStateRefresh(next, maxDelay);
                    }
                }, delay);
            },

            async requestJson(url, options = {}) {
                const headers = {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(options.headers || {}),
                };

                const res = await fetch(url, {
                    credentials: 'same-origin',
                    ...options,
                    headers,
                });

                const contentType = res.headers.get('content-type') || '';
                let data = {};

                if (contentType.includes('application/json')) {
                    data = await res.json();
                } else {
                    data = { error: `Resposta inesperada do servidor (HTTP ${res.status}).` };
                }

                return { res, data };
            },

            async checkState() {
                try {
                    const { res, data } = await this.requestJson(this.urls.connectionState);

                    if (!res.ok) {
                        this.state = 'unknown';
                        return;
                    }

                    const prev = this.state;
                    this.state = data.state ?? 'unknown';

                    if (this.state === 'open') {
                        this.qrBase64 = '';
                        this.infoMsg = '';
                        this.infoTitle = 'Aguardando QR Code';
                        this.errorMsg = '';
                        this.clearPollTimer();
                        this.pollTimer = setTimeout(() => this.checkState(), 30000);
                    } else if (prev === 'open') {
                        this.qrBase64 = '';
                        this.infoMsg = '';
                        this.errorMsg = '';
                    }
                } catch {
                    this.state = 'unknown';
                }
            },

            async logoutInstance() {
                const ok = await window.confirmModal({
                    title: 'Resetar sessão do WhatsApp?',
                    message: 'A sessão atual será encerrada e você precisará escanear um novo QR Code para reconectar.',
                    confirmLabel: 'Resetar',
                    cancelLabel: 'Cancelar',
                });
                if (!ok) return;

                this.loading = true;
                this.errorMsg = '';
                this.infoMsg = '';
                this.qrBase64 = '';

                try {
                    const { data } = await this.requestJson(this.urls.logout, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });

                    if (data.success) {
                        this.state = 'close';
                        this.infoMsg = '';
                        this.errorMsg = '';
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: { message: 'Sessão encerrada. Gere um novo QR Code para reconectar.', type: 'success' },
                        }));
                    } else {
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: { message: data.message ?? 'Não foi possível resetar a sessão.', type: 'error' },
                        }));
                    }
                } catch {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: 'Falha ao conectar com a Evolution-API.', type: 'error' },
                    }));
                } finally {
                    this.loading = false;
                }
            },

            async generateQr(background = false) {
                this.loading = !background;
                this.errorMsg = '';

                if (!background) {
                    this.infoMsg = '';
                    this.qrBase64 = '';
                }

                try {
                    const { res, data } = await this.requestJson(this.urls.qrCode);

                    if (!res.ok) {
                        this.clearPollTimer();
                        this.errorMsg = data.error ?? 'Erro ao gerar QR Code.';
                        return;
                    }

                    this.state = data.state ?? this.state;

                    if (data.status === 'connected') {
                        this.state = 'open';
                        this.qrBase64 = '';
                        this.infoMsg = '';
                        this.errorMsg = '';
                        this.clearPollTimer();
                        return;
                    }

                    if (data.status === 'pending') {
                        const isInit = data.state === 'initializing';
                        this.infoTitle = isInit ? 'Instância inicializando' : 'Aguardando QR Code';
                        this.infoMsg = data.message ?? 'QR Code sendo preparado. Tente novamente em instantes.';
                        this.qrBase64 = '';
                        this.scheduleQrRetry(isInit ? 5000 : 8000);
                        return;
                    }

                    this.qrBase64 = data.base64 ?? '';
                    this.infoMsg = '';
                    this.infoTitle = 'Aguardando QR Code';

                    this.scheduleStateRefresh();

                } catch {
                    this.clearPollTimer();
                    this.errorMsg = 'Falha ao conectar com a Evolution-API.';
                } finally {
                    this.loading = false;
                }
            }
        };
    }
    </script>
    @endif

    <script>
    function whatsappRecentConversations(config) {
        return {
            conversations: config.initial ?? [],
            url: config.url,
            intervalMs: config.intervalMs ?? 15000,
            timer: null,
            polling: false,
            loading: false,
            lastUpdatedLabel: '',
            newlyArrived: new Set(),

            init() {
                this.timer = setInterval(() => this.refresh(false), this.intervalMs);
                window.addEventListener('beforeunload', () => clearInterval(this.timer));
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        clearInterval(this.timer);
                        this.timer = null;
                    } else if (!this.timer) {
                        this.refresh(false);
                        this.timer = setInterval(() => this.refresh(false), this.intervalMs);
                    }
                });
            },

            stateColor(state) {
                return {
                    completed:  'bg-green-100 text-green-700',
                    cancelled:  'bg-red-100 text-red-700',
                    confirming: 'bg-blue-100 text-blue-700',
                }[state] ?? 'bg-yellow-100 text-yellow-700';
            },

            async refresh(manual) {
                if (this.loading) return;
                this.loading = true;
                if (manual) this.polling = true;

                try {
                    const res = await fetch(this.url, {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!res.ok) {
                        return;
                    }

                    const data = await res.json();
                    const incoming = data.conversations ?? [];

                    const existingIds = new Set(this.conversations.map(c => c.id));
                    incoming.forEach(conv => {
                        if (!existingIds.has(conv.id)) {
                            this.newlyArrived.add(conv.id);
                        }
                    });

                    this.conversations = incoming;
                    this.lastUpdatedLabel = 'agora há pouco';

                    if (this.newlyArrived.size > 0) {
                        setTimeout(() => {
                            this.newlyArrived = new Set();
                        }, 6000);
                    }
                } catch (e) {
                    // erro de rede — silencioso
                } finally {
                    this.loading = false;
                    if (manual) {
                        setTimeout(() => { this.polling = false; }, 800);
                    }
                }
            },
        };
    }
    </script>

</div>
@endsection
