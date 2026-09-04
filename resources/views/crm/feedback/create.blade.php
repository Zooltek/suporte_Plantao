@extends('crm.layouts.master-blank')

@section('content')
<div class="bg-white dark:bg-slate-800 p-4 sm:p-6 rounded-lg shadow-sm min-h-screen transition-colors duration-300">

    {{-- ALERTS COM ALPINE.JS --}}
    @if($errors->any())
        <div x-data="{ show: true }" x-show="show" class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
            <div class="flex justify-between items-start">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-circle-exclamation text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Erros encontrados:</h3>
                        <ul class="mt-2 list-disc list-inside text-sm text-red-700">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button @click="show = false" type="button" class="ml-auto bg-red-50 text-red-500 hover:text-red-700 focus:outline-none">
                    <span class="sr-only">Fechar</span>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    @endif

    {{-- FORMULÁRIO PRINCIPAL --}}
    <div class="mt-4 px-2 sm:px-4">
        @php
            $selectedCustomerId = (int) old('customer_id', $customer->id);
            $selectedCustomer = $customers->firstWhere('id', $selectedCustomerId) ?? $customer;
            $fallbackInfo = 'Nao cadastrado';
        @endphp

        <form method="POST" action="{{ route('feedback.store') }}" id="feedback-form">
            @csrf

            @isset($feedback)
                @if(in_array($feedback->status, ['pen', 'open'], true))
                    <input type="hidden" name="feedback_id" value="{{ $feedback->id }}">
                @endif
            @endisset

            <input type="hidden" name="start" value="{{ $start->format('d/m/Y') }}">
            <input type="hidden" name="form_id" value="{{ $form->id }}">

            {{-- SITUAÇÃO --}}
            @if($customer->total)
                <section class="mb-6 bg-gray-50 dark:bg-slate-700/30 border border-gray-200 dark:border-slate-600 rounded-lg p-4">
                    <strong class="block text-gray-700 dark:text-gray-200 mb-2">Situação do Cliente</strong>
                    <div class="text-sm text-gray-600 dark:text-gray-400 flex flex-wrap gap-x-4 gap-y-1">
                        <span>Feedbacks disponíveis: <span id="stat-remaining" class="font-semibold text-gray-900 dark:text-gray-100">{{ $customer->remaining }}</span></span>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <span>Finalizados: <span id="stat-finalized" class="font-semibold text-gray-900 dark:text-gray-100">{{ $customer->finalized }}</span></span>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <span>Adiados: <span id="stat-delay" class="font-semibold text-gray-900 dark:text-gray-100">{{ $customer->delay }}</span></span>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <span>Total: <span id="stat-total" class="font-semibold text-gray-900 dark:text-gray-100">{{ $customer->total }}</span></span>
                    </div>

                    @if($form->id === 2)
                        <p class="mt-2 text-xs text-gray-500">
                            Total de chamados do cliente: <span id="stat-tickets">{{ $tickets_count }}</span>
                        </p>
                    @endif
                </section>
            @endif

            {{-- DADOS DO CLIENTE E CONTATOS --}}
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-6">
                
                {{-- Select Cliente --}}
                <div class="col-span-12 md:col-span-6">
                    <label for="customer_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Cliente <span class="text-red-500">*</span>
                    </label>
                    <select id="customer_id" name="customer_id" class="block w-full rounded-md dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm @error('customer_id') border-red-500 @else border-gray-300 dark:border-slate-600 @enderror" required autofocus>
                        @foreach($customers as $customerOption)
                            <option
                                value="{{ $customerOption->id }}"
                                data-contact-name="{{ $customerOption->contact_name }}"
                                data-contact-email="{{ $customerOption->contact_email }}"
                                data-email="{{ $customerOption->email }}"
                                data-phone="{{ $customerOption->phone }}"
                                data-phone2="{{ $customerOption->telephone_2 }}"
                                data-remaining="{{ $customerOption->remaining }}"
                                data-finalized="{{ $customerOption->finalized }}"
                                data-delay="{{ $customerOption->delay }}"
                                data-total="{{ $customerOption->total }}"
                                data-tickets-count="{{ $customerOption->tickets_count }}"
                                @selected((int) $selectedCustomerId === (int) $customerOption->id)
                            >
                                {{ $customerOption->trade_name }} ({{ $customerOption->id }})
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-12 md:col-span-3">
                    <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1">Contato principal</label>
                    <input
                        id="customer_contact_name"
                        type="text"
                        class="block w-full rounded-md border-gray-300 dark:border-slate-600 bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-gray-400 shadow-sm sm:text-sm cursor-not-allowed"
                        value="{{ $selectedCustomer->contact_name ?: $fallbackInfo }}"
                        readonly
                    >
                </div>

                <div class="col-span-12 md:col-span-3">
                    <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1">Email do contato</label>
                    <input
                        id="customer_contact_email"
                        type="text"
                        class="block w-full rounded-md border-gray-300 dark:border-slate-600 bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-gray-400 shadow-sm sm:text-sm cursor-not-allowed"
                        value="{{ $selectedCustomer->contact_email ?: $fallbackInfo }}"
                        readonly
                    >
                </div>

                <div class="col-span-12 md:col-span-3">
                    <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1">Telefone principal</label>
                    <input
                        id="customer_phone"
                        type="text"
                        class="block w-full rounded-md border-gray-300 dark:border-slate-600 bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-gray-400 shadow-sm sm:text-sm cursor-not-allowed"
                        value="{{ $selectedCustomer->phone ?: $fallbackInfo }}"
                        readonly
                    >
                </div>

                <div class="col-span-12 md:col-span-3">
                    <label class="block text-xs text-gray-500 uppercase tracking-wide mb-1">Telefone secundário</label>
                    <input
                        id="customer_phone2"
                        type="text"
                        class="block w-full rounded-md border-gray-300 dark:border-slate-600 bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-gray-400 shadow-sm sm:text-sm cursor-not-allowed"
                        value="{{ $selectedCustomer->telephone_2 ?: $fallbackInfo }}"
                        readonly
                    >
                </div>
            </div>

            {{-- HISTÓRICO --}}
            <section class="mb-6">
                <h6 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Histórico Recente</h6>
                <div id="resultado-historico" class="border border-gray-200 dark:border-slate-600 rounded-lg p-4 bg-gray-50 dark:bg-slate-800/50 min-h-[50px]">
                    @include('crm.feedback.partials.history-list', ['recentFeedbacks' => $recentFeedbacks])
                </div>
            </section>

            {{-- INPUT CONTATO (LIGAÇÃO) --}}
            <div class="mb-6">
                <label for="contact" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Contato (ligação) <span class="text-red-500">*</span>
                </label>
                <input
                    id="contact"
                    name="contact"
                    type="text"
                    class="block w-full rounded-md dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm uppercase @error('contact') border-red-500 @else border-gray-300 dark:border-slate-600 @enderror"
                    value="{{ old('contact', $feedback->contact ?? ($selectedCustomer->contact_name ?? '')) }}"
                    required
                >
                @error('contact')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div id="record-elements" class="space-y-4">
                @foreach($elementTypes as $elementType)
                    @php
                        $fieldName = $elementType->name;
                        $rawValue = $elementValues[$elementType->id] ?? null;
                        $fieldValue = old($fieldName, $rawValue);
                        $dataString = (string) $elementType->data;
                        
                        if (str_starts_with(trim($dataString), '[') && str_ends_with(trim($dataString), ']')) {
                             $decoded = json_decode($dataString, true);
                             $options = collect(is_array($decoded) ? $decoded : []);
                        } else {
                             $options = collect(explode(';', $dataString));
                        }

                        $options = $options
                            ->map(fn ($option) => trim($option))
                            ->filter()
                            ->values();
                        $inputType = in_array($elementType->type, ['text', 'number', 'date'], true)
                            ? $elementType->type
                            : 'text';
                    @endphp

                    <div>
                        <label for="{{ $fieldName }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $elementType->label }}
                        </label>

                        @if($elementType->type === 'textarea')
                            <textarea
                                id="{{ $fieldName }}"
                                name="{{ $fieldName }}"
                                rows="4"
                                class="block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                            >{{ $fieldValue }}</textarea>
                        @elseif($elementType->type === 'select')
                            <select
                                id="{{ $fieldName }}"
                                name="{{ $fieldName }}"
                                class="block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                            >
                                <option value="">Selecione...</option>
                                @foreach($options as $index => $optionLabel)
                                    <option value="{{ $index }}" @selected((string) $fieldValue === (string) $index)>
                                        {{ $optionLabel }}
                                    </option>
                                @endforeach
                            </select>
                        @elseif($elementType->type === 'radio')
                            <div class="space-y-2 rounded-md border border-gray-200 bg-gray-50 p-3">
                                @foreach($options as $index => $optionLabel)
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 mr-4">
                                        <input
                                            type="radio"
                                            name="{{ $fieldName }}"
                                            value="{{ $index }}"
                                            class="h-4 w-4 border-gray-300 text-brand-600 focus:ring-brand-500"
                                            @checked((string) $fieldValue === (string) $index)
                                        >
                                        <span>{{ $optionLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @elseif($elementType->type === 'checkbox')
                            <input type="hidden" name="{{ $fieldName }}" value="0">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input
                                    id="{{ $fieldName }}"
                                    type="checkbox"
                                    name="{{ $fieldName }}"
                                    value="1"
                                    class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                                    @checked(in_array((string) $fieldValue, ['1', 'true', 'on'], true))
                                >
                                <span>{{ $options->first() ?? 'Marcar opção' }}</span>
                            </label>
                        @else
                            <input
                                id="{{ $fieldName }}"
                                name="{{ $fieldName }}"
                                type="{{ $inputType }}"
                                value="{{ $fieldValue }}"
                                class="block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm"
                            >
                        @endif

                        @error($fieldName)
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>

            {{-- TEXTAREAS CONDICIONAIS --}}
            {{-- 
               Nota: Certifique-se de que o componente <x-feedback.textarea> 
               também foi atualizado para usar classes Tailwind.
            --}}
            @if($form->id === 1)
                <div class="space-y-4 mt-4">
                    <x-feedback.textarea label="Descrição da ligação" name="content" :value="$feedback->content ?? ''"/>
                    <x-feedback.textarea label="Sugestões" name="suggestions" :value="$feedback->suggestions ?? ''"/>
                    <x-feedback.textarea label="Reclamações" name="complaint" :value="$feedback->complaint ?? ''"/>
                </div>
            @endif

            {{-- BOTOES DE ACAO --}}
            @if(!isset($feedback) || in_array($feedback->status, ['pen', 'open'], true))
                <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 mt-6">
                    <button type="button" onclick="window.close(); window.location.href='{{ route('crm.index') }}'" class="inline-flex justify-center items-center px-6 py-2 border border-gray-300 dark:border-slate-600 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-800 hover:bg-gray-50 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-colors">
                        <i class="fa-solid fa-arrow-left mr-2"></i> Voltar
                    </button>
                    <div x-data="{ isSubmitting: false }" @submit.prevent="isSubmitting = true; $el.closest('form').submit()">
                        <button type="submit" id="confirm-btn" :disabled="isSubmitting" :class="isSubmitting ? 'opacity-70 cursor-not-allowed w-full' : 'hover:bg-blue-700 w-full'" class="inline-flex justify-center items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            <i x-show="!isSubmitting" class="fa-solid fa-save mr-2"></i>
                            <svg x-show="isSubmitting" x-cloak class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="isSubmitting ? 'Salvando...' : 'Salvar'">Salvar</span>
                        </button>
                    </div>
                </div>
            @endif
        </form>

        {{-- FORMULÁRIO DE ADIAR/CANCELAR --}}
        @if(!isset($feedback) || !in_array($feedback->status, ['fin', '1'], true))
            <div class="mt-10 pt-6 border-t border-gray-200 dark:border-gray-700">
                <form method="POST" action="{{ route('feedback.cancel') }}" class="bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 rounded-lg p-6">
                    @csrf

                    <input type="hidden" id="cancel_customer_id" name="customer_id" value="{{ $selectedCustomer->id }}">
                    <input type="hidden" name="form_id" value="{{ $form->id }}">
                    @isset($feedback)
                        <input type="hidden" name="feedback_id" value="{{ $feedback->id }}">
                    @endisset

                    <h5 class="text-red-700 font-bold mb-4 flex items-center">
                        <i class="fa-solid fa-clock-rotate-left mr-2"></i> Adiar Feedback
                    </h5>

                    <div class="mb-4">
                        <label for="cancel_content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Motivo <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="cancel_content"
                            name="cancel_content"
                            class="block w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-100 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm"
                            rows="3"
                            required
                        >{{ $feedback->cancel_content ?? '' }}</textarea>
                    </div>

                    <div class="flex justify-end" x-data="{ isSubmitting: false }" @submit.prevent="isSubmitting = true; $el.closest('form').submit()">
                        <button type="submit" :disabled="isSubmitting" :class="isSubmitting ? 'opacity-70 cursor-not-allowed' : 'hover:bg-red-50 dark:hover:bg-red-900/40'" class="inline-flex items-center px-4 py-2 border border-red-300 dark:border-red-800 text-sm font-medium rounded-md text-red-700 dark:text-red-400 bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                            <i x-show="!isSubmitting" class="fa-solid fa-clock-rotate-left mr-2"></i>
                            <svg x-show="isSubmitting" x-cloak class="animate-spin -ml-1 mr-2 h-4 w-4 text-red-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="isSubmitting ? 'Adiando...' : 'Adiar'">Adiar</span>
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection

@section('footer')
<script>
    (() => {
        const customerSelect = document.getElementById('customer_id');
        if (!customerSelect) return;

        const cancelCustomerInput = document.getElementById('cancel_customer_id');
        const contactNameInput = document.getElementById('customer_contact_name');
        const contactEmailInput = document.getElementById('customer_contact_email');
        const phoneInput = document.getElementById('customer_phone');
        const phone2Input = document.getElementById('customer_phone2');
        const contactInput = document.getElementById('contact');
        const fallback = 'Nao cadastrado';

        const setValue = (field, value) => {
            if (!field) return;
            field.value = value && value.trim() !== '' ? value : fallback;
        };

        const formatPhone = (phone) => {
            if (!phone) return fallback;
            const cleaned = ('' + phone).replace(/\D/g, '');
            if (cleaned.length === 11) {
                return cleaned.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            } else if (cleaned.length === 10) {
                return cleaned.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
            }
            return phone;
        };

        const applyCustomerInfo = () => {
            const option = customerSelect.selectedOptions[0];
            if (!option) return;

            const contactName = option.dataset.contactName || '';
            const contactEmail = option.dataset.contactEmail || option.dataset.email || '';
            const phone = option.dataset.phone || '';
            const phone2 = option.dataset.phone2 || '';

            setValue(contactNameInput, contactName);
            setValue(contactEmailInput, contactEmail);
            setValue(phoneInput, formatPhone(phone));
            setValue(phone2Input, formatPhone(phone2));

            // Atualiza estatísticas
            const updateStat = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.textContent = value || '0';
            };

            updateStat('stat-remaining', option.dataset.remaining);
            updateStat('stat-finalized', option.dataset.finalized);
            updateStat('stat-delay', option.dataset.delay);
            updateStat('stat-total', option.dataset.total);
            updateStat('stat-tickets', option.dataset.ticketsCount);

            if (cancelCustomerInput) {
                cancelCustomerInput.value = option.value;
            }

            if (contactInput && (!contactInput.value || contactInput.value.trim() === '')) {
                contactInput.value = contactName || '';
            }

            // Atualiza histórico dinamico
            loadHistory(option.value);
        };

        const loadHistory = (customerId) => {
            const container = document.getElementById('resultado-historico');
            if (!container || !customerId) return;
            
            container.innerHTML = `<div class="flex justify-center items-center py-4">
                                       <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                           <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                           <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                       </svg>
                                       <span class="text-sm text-gray-500 dark:text-gray-400">Carregando histórico...</span>
                                   </div>`;

            let urlTemplate = @json(route('feedback.customer.history', ['id' => 'CUSTOMER_ID_PLACEHOLDER']));
            let url = urlTemplate.replace('CUSTOMER_ID_PLACEHOLDER', customerId);

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Erro na rede');
                    return response.text();
                })
                .then(html => {
                    container.innerHTML = html;
                })
                .catch(error => {
                    console.error('Erro ao carregar histórico:', error);
                    container.innerHTML = `<div class="text-sm text-red-500 p-2">Erro ao carregar o histórico.</div>`;
                });
        };

        customerSelect.addEventListener('change', applyCustomerInfo);
        applyCustomerInfo();
    })();
</script>
@endsection
