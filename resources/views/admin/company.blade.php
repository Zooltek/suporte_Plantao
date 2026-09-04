@extends('layouts.master')

@section('page')
    {{ trans('ticketit::admin.administrator-index-title') }} - Empresas
@endsection

@section('content')
<main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-900">
    @include('shared.errors')

    {{-- Cabeçalho e Ações --}}
    <header class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 mb-8">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-gray-100 tracking-tight">Central de Empresas</h1>
            <p class="text-sm text-slate-500 dark:text-gray-400 mt-1 font-light">
                Gerencie o cadastro, vínculos e responsáveis das empresas parceiras.
            </p>
        </div>

        <nav class="flex flex-wrap items-center gap-3" aria-label="Ações de gestão de empresas">
            <a href="{{ route('company.create') }}"
               class="inline-flex items-center px-4 py-2.5 bg-blue-600 dark:bg-blue-500 border border-transparent rounded-lg font-bold text-sm text-white shadow-md hover:bg-blue-700 dark:hover:bg-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all">
                <i class="fa fa-plus-circle mr-2" aria-hidden="true"></i> Nova Empresa
            </a>
            <a href="{{ url('admin/company/register') }}"
               class="inline-flex items-center px-4 py-2.5 bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 rounded-lg font-bold text-sm text-slate-700 dark:text-gray-200 shadow-sm hover:bg-slate-50 dark:hover:bg-gray-700 transition-all">
                <i class="fa fa-user-plus mr-2" aria-hidden="true"></i> Registrar Usuário
            </a>
            <a href="{{ url('admin/company/vinculo') }}"
               class="inline-flex items-center px-4 py-2.5 bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-600 rounded-lg font-bold text-sm text-slate-700 dark:text-gray-200 shadow-sm hover:bg-slate-50 dark:hover:bg-gray-700 transition-all">
                <i class="fa fa-link mr-2" aria-hidden="true"></i> Vincular Usuário
            </a>
        </nav>
    </header>

    {{-- Tabela de Dados --}}
    <section class="bg-white dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700">
                <thead class="bg-slate-800 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-300 dark:text-gray-300 uppercase tracking-widest w-24">ID</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-300 dark:text-gray-300 uppercase tracking-widest">Nome da Empresa</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-300 dark:text-gray-300 uppercase tracking-widest">CNPJ</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-300 dark:text-gray-300 uppercase tracking-widest">Responsável</th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-slate-300 dark:text-gray-300 uppercase tracking-widest pr-10">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-slate-100 dark:divide-gray-700" x-data="{ selected: null }">
                @forelse($companies as $company)
                    <tr class="transition-colors cursor-pointer hover:bg-slate-50/80 dark:hover:bg-gray-800"
                        :class="selected === {{ $company->id }} ? 'bg-blue-50/50 dark:bg-blue-900/50' : ''"
                        @click="selected = {{ $company->id }}">

                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-slate-400 dark:text-gray-300">
                            #{{ $company->id }}
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-slate-900 dark:text-gray-100">{{ $company->name }}</span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <code class="text-xs px-2 py-1 bg-slate-100 dark:bg-gray-700 rounded text-slate-700 dark:text-gray-200 font-mono">
                                {{ $company->cnpj ?? 'Não informado' }}
                            </code>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($company->responsavel)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300">
                                    <i class="fa fa-user mr-1.5 text-blue-500 dark:text-blue-300" aria-hidden="true"></i>
                                    {{ $company->responsavel->name }}
                                </span>
                            @else
                                <span class="text-xs text-slate-400 dark:text-gray-400 italic">Sem responsável</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-right pr-10">
                            <nav class="inline-flex rounded-lg shadow-sm border border-slate-200 dark:border-gray-700" aria-label="Ações de linha">
                                <a href="{{ route('company.edit', $company->id) }}"
                                   class="px-3 py-2 text-slate-600 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-50 dark:hover:bg-gray-700 transition-colors border-r border-slate-200 dark:border-gray-700 first:rounded-l-lg"
                                   title="Editar">
                                    <i class="fa fa-pencil" aria-hidden="true"></i>
                                </a>
                                <a href="#"
                                   class="px-3 py-2 text-slate-600 dark:text-gray-200 hover:text-slate-900 dark:hover:text-gray-100 hover:bg-slate-50 dark:hover:bg-gray-700 transition-colors last:rounded-r-lg"
                                   title="Ver Detalhes">
                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                </a>
                            </nav>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fa fa-building-o text-slate-200 dark:text-gray-600 text-6xl mb-4" aria-hidden="true"></i>
                                <h3 class="text-lg font-medium text-slate-900 dark:text-gray-100">Nenhuma empresa encontrada</h3>
                                <p class="text-slate-500 dark:text-gray-400 mt-1">Comece cadastrando uma nova empresa no botão acima.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection
