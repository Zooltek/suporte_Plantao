@extends('layouts.agent')

@section('title', 'Editar Empresa - Agente')

@section('content')
@php
    $existingContacts = old('contacts')
        ?? $company->contacts->where('origin', 'support')->map(fn($c) => [
            'name' => $c->name,
            'phone' => $c->phone,
            'email' => $c->email,
            'is_main' => (bool) $c->is_main,
        ])->values()->toArray();
    $financialContacts = $company->contacts->where('origin', 'financeiro')->values();
    if (empty($existingContacts)) {
        $existingContacts = [['name' => '', 'phone' => '', 'email' => '', 'is_main' => true]];
    }
@endphp

<script>
function notesBlock(initial) {
    return {
        value: initial,
        expanded: false,
        autoResize(el) {
            el.style.height = 'auto';
            el.style.height = el.scrollHeight + 'px';
        },
        open() {
            this.expanded = true;
            this.$nextTick(() => { this.$refs.modal.focus(); });
        },
        close() {
            this.expanded = false;
            this.$nextTick(() => { this.autoResize(this.$refs.inline); });
        }
    };
}
function companyEditCnpj(initial) {
    return {
        cnpj: '',
        init() {
            this.cnpj = this.formatCnpj(initial || '');
        },
        formatCnpj(val) {
            return (val || '').replace(/\D/g, '')
                .replace(/^(\d{2})(\d)/, '$1.$2')
                .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/\.(\d{3})(\d)/, '.$1/$2')
                .replace(/(\d{4})(\d)/, '$1-$2')
                .substring(0, 18);
        },
        onInput(el) {
            this.cnpj = this.formatCnpj(el.value);
        }
    };
}
function companyEditContacts() {
    return {
        contacts: @json($existingContacts),
        formatPhone(val) {
            const digits = (val || '').replace(/\D/g, '').slice(0, 11);
            if (digits.length <= 2) return digits;
            if (digits.length <= 6) return `(${digits.slice(0,2)}) ${digits.slice(2)}`;
            if (digits.length <= 10) return `(${digits.slice(0,2)}) ${digits.slice(2,6)}-${digits.slice(6)}`;
            return `(${digits.slice(0,2)}) ${digits.slice(2,7)}-${digits.slice(7)}`;
        },
        addContact() {
            this.contacts.push({ name: '', phone: '', email: '', is_main: false });
        },
        removeContact(i) {
            if (this.contacts.length <= 1) return;
            var wasMain = this.contacts[i].is_main;
            this.contacts.splice(i, 1);
            if (wasMain) this.contacts[0].is_main = true;
        },
        setMain(i) {
            this.contacts.forEach(function(c, j) { c.is_main = (j === i); });
        }
    };
}
</script>

<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Editar Empresa</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $company->trade_name ?? $company->name }}</p>
        </div>
        <a href="{{ route('agent.companies.manage.index') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-orange-600 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Voltar
        </a>
    </div>

    @if($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('agent.companies.manage.update', $company->id) }}"
          class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-6">
        @csrf
        @method('PUT')

        {{-- Identificação --}}
        <div>
            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 pb-2 border-b border-gray-100">Identificação</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Razão Social *</label>
                    <input type="text" name="name" value="{{ old('name', $company->name) }}" required
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Nome Fantasia</label>
                    <input type="text" name="trade_name" value="{{ old('trade_name', $company->trade_name) }}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition">
                </div>

                <div x-data="companyEditCnpj(@js(old('cnpj', $company->cnpj) ?? ''))">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">CNPJ</label>
                    <input type="text" name="cnpj" x-model="cnpj"
                           @input="onInput($event.target)"
                           placeholder="00.000.000/0000-00" maxlength="18" inputmode="numeric"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Código Municipal (IBGE)</label>
                    <input type="text" name="city_registration" value="{{ old('city_registration', $company->city_registration) }}"
                           inputmode="numeric" pattern="[0-9]{7}" maxlength="7"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Inscrição Estadual</label>
                    <input type="text" name="state_registration" value="{{ old('state_registration', $company->state_registration) }}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Grupo Empresarial</label>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 flex items-center justify-between gap-3">
                        <span class="text-sm font-medium text-gray-700">{{ $company->group?->name ?: 'Não informado' }}</span>
                        @if($company->group?->financial_code)
                            <span class="rounded bg-indigo-50 px-2 py-0.5 text-xs font-bold text-indigo-700 border border-indigo-200">
                                {{ $company->group->financial_code }}
                            </span>
                        @endif
                    </div>
                    <p class="mt-1 text-[11px] text-gray-400">Campo controlado pelo Financeiro e não editável pelo Suporte.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Telefone</label>
                    <input type="text" name="phone" value="{{ old('phone', $company->phone) }}"
                           placeholder="(00) 0000-0000"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Telefone 2</label>
                    <input type="text" name="telephone_2" value="{{ old('telephone_2', $company->telephone_2) }}"
                           placeholder="(00) 0000-0000"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition">
                </div>
            </div>
        </div>

        {{-- Localização --}}
        <div>
            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 pb-2 border-b border-gray-100">Localização</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Cidade</label>
                    <input type="text" name="city" value="{{ old('city', $company->city) }}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Bairro</label>
                    <input type="text" name="bairro" value="{{ old('bairro', $company->bairro) }}"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition">
                </div>
            </div>
        </div>

        {{-- Observações --}}
        <div x-data="notesBlock({{ json_encode(old('observations', $company->observations ?? '')) }})">
            <div class="flex items-center justify-between mb-1.5">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Observações</label>
                <button type="button" @click="open()"
                        class="inline-flex items-center gap-1 text-xs font-semibold transition-opacity hover:opacity-70"
                        style="color:#f97316">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                    </svg>
                    Expandir
                </button>
            </div>
            <textarea name="observations"
                      x-ref="inline"
                      x-model="value"
                      @input="autoResize($refs.inline)"
                      x-init="autoResize($refs.inline)"
                      placeholder="Observações internas sobre esta empresa..."
                      class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition resize-none overflow-hidden"
                      style="min-height:80px"></textarea>
            <p class="text-right text-[11px] mt-1" style="color:#6b7280" x-text="value.length + ' caracteres'"></p>

            {{-- Modal fullscreen --}}
            <div x-show="expanded"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="fixed inset-0 z-50 flex flex-col"
                 style="background:rgba(10,18,30,0.97)"
                 @keydown.escape.window="close()"
                 x-cloak>
                <div class="flex items-center justify-between px-5 py-3 shrink-0" style="border-bottom:1px solid #1e3a5f">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4" style="color:#f97316" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="text-xs font-bold uppercase tracking-wider" style="color:#94a3b8">Observações</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-xs" style="color:#475569" x-text="value.length + ' caracteres'"></span>
                        <button type="button" @click="close()"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold transition-opacity hover:opacity-70"
                                style="color:#f97316">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Fechar (Esc)
                        </button>
                    </div>
                </div>
                <div class="flex-1 p-4 overflow-hidden">
                    <textarea x-ref="modal"
                              x-model="value"
                              placeholder="Observações internas sobre esta empresa..."
                              class="w-full h-full px-5 py-4 rounded-xl text-sm leading-relaxed focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent resize-none"
                              style="background:#0f172a;color:#e2e8f0;border:1px solid #1e3a5f;font-family:inherit"></textarea>
                </div>
            </div>
        </div>

        {{-- Contatos --}}
        <div>
            <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 pb-2 border-b border-gray-100">Contatos</h2>
            @if($financialContacts->isNotEmpty())
                <div class="mb-5 space-y-2">
                    <p class="text-xs text-gray-500">Contatos sincronizados pelo Financeiro</p>
                    @foreach($financialContacts as $contact)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 rounded-xl border border-indigo-100 bg-indigo-50/50 px-4 py-3">
                            <div>
                                <span class="block text-[11px] font-semibold uppercase tracking-wider text-indigo-400">Nome</span>
                                <span class="text-sm font-medium text-gray-700">{{ $contact->name }}</span>
                            </div>
                            <div>
                                <span class="block text-[11px] font-semibold uppercase tracking-wider text-indigo-400">E-mail</span>
                                <span class="text-sm text-gray-700">{{ $contact->email ?: '—' }}</span>
                            </div>
                        </div>
                    @endforeach
                    <p class="text-[11px] text-gray-400">Esses contatos são informativos e atualizados exclusivamente pela integração.</p>
                </div>
            @endif
            <div x-data="companyEditContacts()">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs text-gray-500">Adicione um ou mais contatos. Defina o principal clicando na estrela.</p>
                    <button type="button" @click="addContact()"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-orange-600 hover:text-orange-700 transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        Adicionar contato
                    </button>
                </div>

                <div class="space-y-3">
                    <template x-for="(contact, i) in contacts" :key="i">
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Nome</label>
                                    <input type="text" :name="`contacts[${i}][name]`" x-model="contact.name"
                                           placeholder="Nome do contato"
                                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition bg-white">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Telefone</label>
                                    <input type="text" :name="`contacts[${i}][phone]`" x-model="contact.phone"
                                           @input="contact.phone = formatPhone($event.target.value)"
                                           placeholder="(00) 00000-0000"
                                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition bg-white">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">E-mail</label>
                                    <input type="email" :name="`contacts[${i}][email]`" x-model="contact.email"
                                           placeholder="contato@empresa.com.br"
                                           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition bg-white">
                                </div>
                            </div>
                            <input type="hidden" :name="`contacts[${i}][is_main]`" :value="contact.is_main ? '1' : '0'">
                            <div class="flex items-center justify-between">
                                <button type="button" @click="setMain(i)"
                                        :class="contact.is_main ? 'text-orange-600 font-semibold' : 'text-gray-400 hover:text-orange-500'"
                                        class="inline-flex items-center gap-1.5 text-xs transition-colors">
                                    <svg class="h-4 w-4" :class="contact.is_main ? 'fill-orange-500 stroke-none' : 'fill-none stroke-current'" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118L2.062 9.101c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                    </svg>
                                    <span x-text="contact.is_main ? 'Contato principal' : 'Definir como principal'"></span>
                                </button>
                                <button type="button" @click="removeContact(i)" x-show="contacts.length > 1"
                                        class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-red-500 transition-colors">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Remover
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Módulos + Status Financeiro --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Módulos Contratados</label>
                @if($moduleTypes->isEmpty())
                    <p class="text-xs text-gray-400 italic">Nenhum módulo cadastrado. <a href="{{ route('admin.helpdesk.modules.index') }}" class="text-orange-500 hover:underline">Cadastrar módulos</a></p>
                @else
                    <div class="flex flex-wrap gap-4">
                        @php $oldModules = old('module_ids', $activeModuleIds) @endphp
                        @foreach($moduleTypes as $module)
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" name="module_ids[]" value="{{ $module->id }}"
                                       {{ in_array($module->id, $oldModules) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-gray-300 text-orange-500 focus:ring-orange-400">
                                <span class="text-sm font-medium text-gray-700">{{ $module->name }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Situação do Cliente</label>
                <div class="grid gap-3">
                    <div class="p-3 rounded-xl border border-amber-200 bg-amber-50">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider {{ $company->is_active ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                            {{ $company->is_active ? 'Cliente ativo' : 'Cliente inativo' }}
                        </span>
                    </div>
                    <div class="p-3 rounded-xl border border-amber-200 bg-amber-50">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider {{ $company->financial_irregular ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200' }}">
                            {{ $company->financial_irregular ? 'Cliente inadimplente' : 'Cliente regular' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-between items-center pt-2" style="border-top:1px solid #374151">
            <a href="{{ route('agent.company.history', $company->id) }}"
               style="color:#60a5fa"
               class="text-sm font-medium transition-opacity hover:opacity-80">
                Ver histórico da empresa →
            </a>
            <div class="flex gap-3">
                <a href="{{ route('agent.companies.manage.index') }}"
                   style="background-color:#374151;color:#e5e7eb"
                   class="px-5 py-2.5 text-sm font-semibold rounded-xl transition-opacity hover:opacity-80">
                    Cancelar
                </a>
                <button type="submit"
                        style="background-color:#f97316;color:#ffffff"
                        class="px-6 py-2.5 text-sm font-semibold rounded-xl shadow-sm hover:opacity-90 transition-opacity">
                    Salvar Alterações
                </button>
            </div>
        </div>
    </form>

</div>
@endsection
