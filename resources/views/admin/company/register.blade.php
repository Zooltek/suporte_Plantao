@extends('layouts.app')

@section('content')
<main class="min-h-screen flex flex-col items-center justify-center bg-gray-50 px-4 py-12" x-data="{ submitting: false }">
    <div class="w-full max-w-md">
        <header class="flex justify-center mb-8">
            <a href="{{ url('login') }}" class="transition-transform hover:scale-105" aria-label="Ir para o login">
                <img class="h-16 w-auto" src="{{ asset('img/logo.png') }}" alt="Logo da Empresa">
            </a>
        </header>

        {{-- card de registro --}}
        <section class="bg-white shadow-xl rounded-xl border border-gray-100 overflow-hidden">
            <div class="bg-white border-b border-gray-100 py-6 text-center">
                <h1 class="text-2xl font-extrabold text-gray-800">Criar Conta</h1>
                <p class="text-sm text-gray-500 mt-1">Cadastre-se para acessar o sistema</p>
            </div>

            <div class="p-8">
                <form method="POST" action="{{ url('/register') }}" @submit="submitting = true" class="space-y-5">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-bold text-gray-700 mb-1">Nome Completo</label>
                        <input id="name" type="text"
                               name="name" value="{{ old('name') }}" required autofocus
                               @class([
                                   'block w-full rounded-lg shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 transition-colors',
                                   'border-red-500 text-red-900 placeholder-red-300' => $errors->has('name'),
                                   'border-gray-300' => !$errors->has('name'),
                               ])>
                        @error('name')
                            <p class="mt-1 text-xs text-red-600 font-semibold italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-bold text-gray-700 mb-1">Endereço de E-mail</label>
                        <input id="email" type="email"
                               name="email" value="{{ old('email') }}" required
                               @class([
                                   'block w-full rounded-lg shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 transition-colors',
                                   'border-red-500 text-red-900 placeholder-red-300' => $errors->has('email'),
                                   'border-gray-300' => !$errors->has('email'),
                               ])>
                        @error('email')
                            <p class="mt-1 text-xs text-red-600 font-semibold italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-bold text-gray-700 mb-1">Senha</label>
                        <input id="password" type="password"
                               name="password" required
                               @class([
                                   'block w-full rounded-lg shadow-sm sm:text-sm focus:ring-blue-500 focus:border-blue-500 transition-colors',
                                   'border-red-500' => $errors->has('password'),
                                   'border-gray-300' => !$errors->has('password'),
                               ])>
                        @error('password')
                            <p class="mt-1 text-xs text-red-600 font-semibold italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password-confirm" class="block text-sm font-bold text-gray-700 mb-1">Confirmar Senha</label>
                        <input id="password-confirm" type="password"
                               name="password_confirmation" required
                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-colors">
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all disabled:opacity-50 disabled:cursor-wait"
                                :disabled="submitting">
                            <span x-show="!submitting" class="flex items-center">
                                <i class="fa fa-user-plus mr-2" aria-hidden="true"></i> Registrar
                            </span>
                            <span x-show="submitting" class="flex items-center" x-cloak>
                                <i class="fas fa-circle-notch fa-spin mr-2" aria-hidden="true"></i> Processando...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <nav class="text-center mt-6">
            <p class="text-sm text-gray-600">
                Já tem uma conta?
                <a href="{{ route('login') }}" class="font-bold text-blue-600 hover:text-blue-800 transition-colors">
                    Entrar
                </a>
            </p>
        </nav>
    </div>
</main>
@endsection
