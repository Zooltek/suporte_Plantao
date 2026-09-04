{{--
    Confirm Modal — componente global de confirmação para ações destrutivas.

    Uso via JS:
        const ok = await window.confirmModal({
            title:        'Excluir usuário?',
            message:      'Esta ação não pode ser desfeita.',
            confirmLabel: 'Excluir',   // opcional, padrão: 'Confirmar'
            cancelLabel:  'Cancelar',  // opcional
        });
        if (!ok) return;
--}}
<div
    x-data="confirmModal"
    @confirm-modal:open.window="open($event.detail)"
    @keydown.escape.window="cancel()"
    x-show="isOpen"
    x-cloak
    class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    :aria-labelledby="isOpen ? 'cm-title' : undefined"
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-black/50 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="cancel()"
        aria-hidden="true"
    ></div>

    {{-- Card --}}
    <div
        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        @click.stop
    >
        {{-- Faixa superior vermelha --}}
        <div class="h-1.5 bg-gradient-to-r from-red-500 to-rose-600 rounded-t-2xl"></div>

        {{-- Corpo --}}
        <div class="flex flex-col items-center px-6 pt-7 pb-5 text-center">
            {{-- Ícone de aviso --}}
            <div class="w-14 h-14 rounded-full bg-red-50 border border-red-100 flex items-center justify-center mb-4 flex-shrink-0">
                <svg class="w-7 h-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
            </div>

            {{-- Título --}}
            <h3 id="cm-title" class="text-[15px] font-bold text-gray-900 mb-1" x-text="title"></h3>

            {{-- Mensagem --}}
            <p class="text-sm text-gray-500 leading-relaxed" x-text="message"></p>
        </div>

        {{-- Ações --}}
        <div class="flex gap-3 px-6 pb-6">
            <button
                x-ref="cancelBtn"
                @click="cancel()"
                type="button"
                class="flex-1 py-2.5 px-4 rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-300"
                x-text="cancelLabel"
            ></button>
            <button
                @click="confirm()"
                type="button"
                class="flex-1 py-2.5 px-4 rounded-xl bg-red-600 text-sm font-semibold text-white hover:bg-red-700 active:scale-95 transition-all focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                x-text="confirmLabel"
            ></button>
        </div>
    </div>
</div>
