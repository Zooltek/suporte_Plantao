<x-guest-layout>
    <div class="text-center py-12">
        <h1 class="text-3xl font-bold mb-6">Suporte Sistema</h1>
        <p class="mb-8">Gestão de chamados e atendimento</p>

        <div class="flex justify-center space-x-4">
            <a href="{{ route('login') }}" class="px-4 py-2 bg-indigo-600 text-white rounded">
                Acessar Sistema
            </a>
        </div>

        <!-- Mostrar versão do Laravel -->
        <p class="mt-8 text-sm text-gray-500">
            Laravel v{{ app()->version() }}
        </p>
    </div>
</x-guest-layout>


