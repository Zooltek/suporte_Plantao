@extends('admin.layouts.master')

@section('content')
    @php
        $rowsByKey = collect($rows)->keyBy('key');
    @endphp

    <div class="py-8" x-data="slaSettingsForm" data-rows='@json($rows)'>
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800 tracking-tight">SLA em Minutos</h1>
            <p class="text-gray-500 font-light mt-1">
                Defina os tempos de vencimento por severidade de prioridade.
            </p>
        </div>

        @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <section class="rounded-xl border border-indigo-100 bg-indigo-50 px-5 py-4">
                <h2 class="text-sm font-bold text-indigo-800">Pesos das Categorias (Explícito)</h2>
                <p class="mt-1 text-xs text-indigo-700">
                    Urgente = <strong>{{ \App\Models\Category::getPriorityWeightByLevel(\App\Models\Category::PRIORITY_URGENT) }}</strong>,
                    Alta = <strong>{{ \App\Models\Category::getPriorityWeightByLevel(\App\Models\Category::PRIORITY_HIGH) }}</strong>,
                    Baixa = <strong>{{ \App\Models\Category::getPriorityWeightByLevel(\App\Models\Category::PRIORITY_LOW) }}</strong>.
                </p>
                <p class="mt-1 text-xs text-indigo-700">
                    Cálculo do ticket: <strong>Peso Total = Peso da Categoria + Peso da Subcategoria</strong>.
                </p>
                <a href="{{ route('admin.categories.index') }}"
                   class="mt-3 inline-flex items-center rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-bold text-indigo-700 hover:bg-indigo-100 transition-colors">
                    Ver categorias e pesos
                </a>
            </section>

            <section class="rounded-xl border border-blue-100 bg-blue-50 px-5 py-4">
                <h2 class="text-sm font-bold text-blue-800">Faixas de SLA por Peso Total</h2>
                <div class="mt-2 space-y-2 text-xs text-blue-700">
                    <p>
                        <strong>Peso &ge; 8 (Alta):</strong>
                        Atenção {{ $rowsByKey['high']['attention'] }} min,
                        Aviso {{ $rowsByKey['high']['warning'] }} min,
                        Crítico {{ $rowsByKey['high']['critical'] }} min.
                    </p>
                    <p>
                        <strong>Peso &ge; 5 e &lt; 8 (Média):</strong>
                        Atenção {{ $rowsByKey['med']['attention'] }} min,
                        Aviso {{ $rowsByKey['med']['warning'] }} min,
                        Crítico {{ $rowsByKey['med']['critical'] }} min.
                    </p>
                    <p>
                        <strong>Peso &lt; 5 (Baixa):</strong>
                        Atenção {{ $rowsByKey['low']['attention'] }} min,
                        Aviso {{ $rowsByKey['low']['warning'] }} min,
                        Crítico {{ $rowsByKey['low']['critical'] }} min.
                    </p>
                </div>
            </section>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <form method="POST" action="{{ route('admin.sla.update') }}" class="p-6" @submit.prevent="submit($event)">
                @csrf
                @method('PUT')

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nível</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Atenção (min)</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Aviso (min)</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Crítico (min)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="row in rows" :key="row.key">
                                <tr>
                                    <td class="px-4 py-4 text-sm font-semibold text-gray-700" x-text="row.label"></td>
                                    <td class="px-4 py-4">
                                        <input
                                            type="number"
                                            min="1"
                                            step="1"
                                            :name="`sla_${row.key}_attention`"
                                            x-model.number="row.attention"
                                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            required
                                        >
                                    </td>
                                    <td class="px-4 py-4">
                                        <input
                                            type="number"
                                            min="1"
                                            step="1"
                                            :name="`sla_${row.key}_warning`"
                                            x-model.number="row.warning"
                                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            required
                                        >
                                    </td>
                                    <td class="px-4 py-4">
                                        <input
                                            type="number"
                                            min="1"
                                            step="1"
                                            :name="`sla_${row.key}_critical`"
                                            x-model.number="row.critical"
                                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            required
                                        >
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div x-show="clientErrors.length" x-cloak class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    <ul class="list-disc pl-5">
                        <template x-for="message in clientErrors" :key="message">
                            <li x-text="message"></li>
                        </template>
                    </ul>
                </div>

                <div class="mt-6 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                    Regra aplicada: <strong>Atenção &lt; Aviso &lt; Crítico</strong> em cada nível.
                    A ordem entre níveis também é validada: <strong>Alta &lt;= Média &lt;= Baixa</strong>.
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="inline-flex items-center px-5 py-2.5 bg-blue-600 border border-transparent rounded-lg font-bold text-sm text-white shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        <span x-show="!submitting">Salvar tempos de SLA</span>
                        <span x-show="submitting">Salvando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
