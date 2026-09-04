<x-guest-layout>
    <div class="text-center mb-8 flex flex-col items-center justify-center">
        <div class="w-14 h-14 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center mb-4">
            <svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h2 class="text-xl font-extrabold text-gray-900 dark:text-white tracking-tight">
            Troca de senha obrigatória
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1.5 leading-relaxed">
            Sua conta foi criada com uma senha temporária.<br>
            Defina uma nova senha para continuar.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200">
            <ul class="list-disc list-inside text-sm text-red-700 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.force-change.update') }}" class="space-y-4">
        @csrf

        <div>
            <label for="password" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                Nova senha
            </label>
            <input id="password"
                   type="password"
                   name="password"
                   required
                   autocomplete="new-password"
                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-slate-600
                          bg-white dark:bg-slate-700 text-gray-900 dark:text-white text-sm
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
            <p class="mt-1 text-[11px] text-gray-400">Mínimo de 8 caracteres.</p>
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                Confirmar nova senha
            </label>
            <input id="password_confirmation"
                   type="password"
                   name="password_confirmation"
                   required
                   autocomplete="new-password"
                   class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-slate-600
                          bg-white dark:bg-slate-700 text-gray-900 dark:text-white text-sm
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
        </div>

        <button type="submit"
                class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold
                       transition-all shadow-sm active:scale-95 mt-2">
            Salvar nova senha
        </button>
    </form>
</x-guest-layout>
