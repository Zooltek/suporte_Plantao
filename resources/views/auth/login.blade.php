<x-guest-layout>
    <div class="space-y-6 sm:space-y-8">
    <div class="login-header mb-6 flex flex-col items-center justify-center text-center sm:mb-8">
        <a href="{{ route('login') }}" class="mb-3 flex items-center justify-center sm:mb-4">
            <img src="{{ asset('img/amura-logo-light.png') }}" alt="Amura" class="h-12 w-auto dark:hidden sm:h-16">
            <img src="{{ asset('img/amura-logo-dark.png') }}" alt="Amura" class="hidden h-12 w-auto dark:block sm:h-16">
        </a>
        <h2 class="text-xl font-extrabold tracking-tight text-gray-900 dark:text-white sm:text-2xl">
            @if(Route::currentRouteName() === 'admin.login')
                {{ __('Acesso Restrito') }}
            @else
                {{ __('Bem-vindo ao Suporte') }}
            @endif
        </h2>
        <p class="login-subtitle mt-2 text-sm text-gray-500 dark:text-gray-400">
            Insira suas credenciais para continuar.
        </p>
    </div>
    <form id="submit-login"
          method="POST"
          action="{{ Route::currentRouteName() === 'admin.login' ? route('admin.login') : route('login') }}"
          class="space-y-5 sm:space-y-6">
        @csrf

        <!-- Usuário -->
        <div>
            <x-input-label for="email" :value="__('Usuário')" />
            <x-text-input id="email" name="email" type="email"
                          class="mt-1 block w-full"
                          value="{{ old('email') }}"
                          autocomplete="username"
                          autofocus />

            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Senha -->
        <div>
            <x-input-label for="password" :value="__('Senha')" />
            <x-text-input id="password" class="mt-1 block w-full"
                          type="password"
                          name="password"
                          autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Manter conectado -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox"
                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                       name="remember">
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Manter conectado') }}
                </span>
            </label>

            @if(Route::currentRouteName() === 'admin.login')
                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100"
                   href="{{ route('admin.password.request') }}">
                    {{ __('Esqueceu sua senha?') }}
                </a>
            @else
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100"
                       href="{{ route('password.request') }}">
                        {{ __('Esqueceu sua senha?') }}
                    </a>
                @endif
            @endif
        </div>

        <!-- Botão -->
        <div class="pt-1 sm:pt-2">
            <button type="submit" class="flex w-full items-center justify-center rounded-xl border border-transparent bg-gradient-to-r from-orange-500 to-rose-500 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:from-orange-600 hover:to-rose-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 sm:py-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                {{ __('Acessar Conta') }}
            </button>
        </div>
    </form>
    </div>
</x-guest-layout>
