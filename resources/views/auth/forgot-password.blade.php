<x-guest-layout>
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            @if(Route::currentRouteName() === 'admin.password.request')
                {{ __('Recuperar Senha - Área do Administrador') }}
            @else
                {{ __('Recuperar Senha') }}
            @endif
        </h2>
    </div>

    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400 text-center">
        {{ __('Esqueceu sua senha? Sem problema. Basta informar seu endereço de e-mail e nós enviaremos um link de redefinição de senha que permitirá que você escolha uma nova.') }}
    </div>
    <form method="POST"
          action="{{ Route::currentRouteName() === 'admin.password.request' ? route('admin.password.email') : route('password.email') }}">
        @csrf

        <div class="text-left">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full"
                          type="email"
                          name="email"
                          :value="old('email')"
                          required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-center mt-6">
            <x-primary-button class="w-full sm:w-auto justify-center">
                {{ __('Link de Redefinição de Senha por E-mail') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
