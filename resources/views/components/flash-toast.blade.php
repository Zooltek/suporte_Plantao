@php
    $rawStatus = session('status');

    $statusMessage = match ($rawStatus) {
        'verification-link-sent' => __('Um novo link de verificação foi enviado para o seu endereço de e-mail.'),
        'profile-updated' => __('Perfil atualizado com sucesso.'),
        'password-updated' => __('Senha atualizada com sucesso.'),
        default => $rawStatus,
    };

    $initialToasts = collect([
        ['message' => session('success'), 'type' => 'success'],
        ['message' => $statusMessage, 'type' => 'success'],
        ['message' => session('warning'), 'type' => 'warning'],
        ['message' => session('error'), 'type' => 'error'],
        ['message' => session('info'), 'type' => 'info'],
    ])
        ->filter(fn ($toast) => filled($toast['message']))
        ->values()
        ->all();
@endphp

<div x-data="flashCenter(@js($initialToasts))"
     x-cloak
     class="pointer-events-none fixed inset-x-0 top-4 z-[90] px-4 sm:inset-x-auto sm:right-5 sm:top-5 sm:px-0 print:hidden">
    <style>
        @keyframes flash-toast-progress {
            from { width: 100%; }
            to { width: 0%; }
        }
    </style>

    <div class="flex w-full flex-col gap-3 sm:w-[24rem]">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.visible"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-3"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:translate-x-0"
                 x-transition:leave-end="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-3"
                 class="pointer-events-auto overflow-hidden rounded-2xl border shadow-xl backdrop-blur-sm"
                 :class="themeFor(toast.type).panel"
                 :role="toast.type === 'error' ? 'alert' : 'status'"
                 :aria-live="toast.type === 'error' ? 'assertive' : 'polite'"
                 aria-atomic="true">
                <div class="flex items-start gap-3 px-4 py-3.5">
                    <div class="mt-0.5 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full"
                         :class="themeFor(toast.type).iconWrap"
                         aria-hidden="true">
                        <svg x-show="toast.type === 'success'" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <svg x-show="toast.type === 'error'" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-4.293-2.293a1 1 0 00-1.414 0L10 10.586 7.707 8.293a1 1 0 00-1.414 1.414L8.586 12l-2.293 2.293a1 1 0 101.414 1.414L10 13.414l2.293 2.293a1 1 0 001.414-1.414L11.414 12l2.293-2.293a1 1 0 000-1.414z" clip-rule="evenodd"/>
                        </svg>
                        <svg x-show="toast.type === 'warning'" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l6.518 11.596c.75 1.334-.213 2.995-1.742 2.995H3.48c-1.53 0-2.492-1.66-1.743-2.995L8.257 3.1zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-6a1 1 0 00-.993.883L9 8v3a1 1 0 001.993.117L11 11V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <svg x-show="toast.type === 'info'" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-8-3a1.25 1.25 0 100-2.5A1.25 1.25 0 0010 7zm1 2a1 1 0 10-2 0v4a1 1 0 102 0V9z" clip-rule="evenodd"/>
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <span class="sr-only" x-text="labelFor(toast.type)"></span>
                        <p class="text-sm font-semibold leading-5" :class="themeFor(toast.type).message" x-text="toast.message"></p>
                    </div>

                    <button type="button"
                            @click="dismissToast(toast.id)"
                            class="rounded-full p-1 transition-colors focus:outline-none focus-visible:ring-2"
                            :class="themeFor(toast.type).close"
                            aria-label="Fechar mensagem">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="h-1 w-full" :class="themeFor(toast.type).track" aria-hidden="true">
                    <div class="h-full w-full" :class="themeFor(toast.type).progress" :style="progressStyle(toast)"></div>
                </div>
            </div>
        </template>
    </div>
</div>
