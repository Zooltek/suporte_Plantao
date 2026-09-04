<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Suporte - Módulo de Tarefas')</title>
    
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('favicon.ico') }}">
    
    <!-- Prevent FOUC (Flash of Unstyled Content) for Dark Mode -->
    <script>
        if (localStorage.getItem('theme') === 'ocean' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('ocean');
        } else {
            document.documentElement.classList.remove('ocean');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
    @stack('styles')
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-800 transition-colors duration-300">

    @php
        $isTaskAdmin = auth('admin')->check() && (bool) auth('admin')->user()->ticketit_admin;
    @endphp

    <div class="min-h-screen flex flex-col" x-data="{ sidebarOpen: false }">

        {{-- Top Navbar --}}
        <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-200 shadow-sm transition-colors duration-300 print:hidden">
            <div class="px-4 lg:px-6 h-14 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    {{-- Mobile hamburger --}}
                    <button @click.stop="sidebarOpen = !sidebarOpen"
                            class="lg:hidden p-2 -ml-1 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    {{-- Logo --}}
                    <a href="{{ route('tasks.index') }}" class="flex items-center gap-2.5">
                        <img src="{{ asset('img/amura-logo-light.png') }}" alt="Amura" class="h-8 w-auto dark:hidden">
                        <img src="{{ asset('img/amura-logo-dark.png') }}" alt="Amura" class="h-8 w-auto hidden dark:block">
                        <span class="font-bold text-lg bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600 tracking-tight hidden sm:inline ml-2 border-l border-gray-300 dark:border-gray-600 pl-2">Tarefas</span>
                    </a>
                </div>

                {{-- Right actions --}}
                <div class="flex items-center gap-4">
                    <div class="hidden md:block text-sm text-gray-500">
                        Bem-vindo, <span class="font-semibold text-gray-800">{{ auth('admin')->user()->name ?? auth()->user()->name ?? 'Usuário' }}</span>
                    </div>
                    <div class="scale-90">
                        <x-theme-toggle />
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                        @csrf
                        <button type="submit"
                                class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:text-red-500 hover:bg-red-50 transition-colors"
                                title="Sair">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Layout: Sidebar + Content --}}
        <div class="flex-grow flex relative">

            {{-- Mobile Overlay --}}
            <div x-show="sidebarOpen" x-cloak
                 @click="sidebarOpen = false"
                 x-transition:enter="transition-opacity ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-black/30 backdrop-blur-sm z-40 lg:hidden print:hidden">
            </div>

            {{-- Sidebar --}}
            <aside class="fixed lg:sticky top-14 left-0 z-40 h-[calc(100vh-3.5rem)] w-60 bg-white border-r border-gray-100 overflow-y-auto transition-transform duration-300 ease-in-out lg:translate-x-0 flex flex-col shadow-sm print:hidden"
                   :class="sidebarOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full'"
                   @click.outside="sidebarOpen = false">

                <nav class="flex-1 px-3 py-5 space-y-6">

                    {{-- MINHAS TAREFAS --}}
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 px-3 mb-2">Inbox</p>
                        <div class="space-y-0.5">
                            <a href="{{ route('tasks.index') }}"
                               class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('tasks.*') ? 'bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-700 font-semibold border border-indigo-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                Minhas Tarefas
                            </a>
                        </div>
                    </div>

                    {{-- RELATÓRIOS --}}
                    @if($isTaskAdmin)
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 px-3 mb-2">Relatórios</p>
                        <div class="space-y-0.5">

                            <a href="{{ route('admin.tasks.report.carlos') }}"
                               class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.tasks.report.carlos') ? 'bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-700 font-semibold border border-indigo-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Relatório por Módulo
                            </a>

                            <a href="{{ route('admin.tasks.report.por-cliente') }}"
                               class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.tasks.report.por-cliente') ? 'bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-700 font-semibold border border-indigo-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                Solicitações por Cliente
                            </a>

                            <a href="{{ route('admin.tasks.report.por-modulo') }}"
                               class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('admin.tasks.report.por-modulo') ? 'bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-700 font-semibold border border-indigo-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                Label por Módulo
                            </a>
                        </div>
                    </div>
                    @endif
                    
                    {{-- NAVEGAÇÃO --}}
                    @if(count($navigationItems ?? []) > 0)
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 px-3 mb-2">Navegação</p>
                        <div class="space-y-0.5">
                            @foreach($navigationItems as $navItem)
                            <a href="{{ route($navItem['route']) }}"
                               class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 text-gray-600 {{ $navItem['hoverBg'] }} {{ $navItem['hoverText'] }} mt-1 border border-dashed border-gray-200 {{ $navItem['hoverBorder'] }}">
                                <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $navItem['iconSvg'] !!}</svg>
                                {{ $navItem['label'] }}
                                <svg class="w-3 h-3 ml-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                </nav>

                {{-- Sidebar Footer --}}
                <div class="px-4 py-3 border-t border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-purple-400 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                            {{ auth('admin')->check() ? strtoupper(substr(auth('admin')->user()->name, 0, 1)) : (auth()->check() ? strtoupper(substr(auth()->user()->name, 0, 1)) : 'U') }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-800 truncate">{{ auth('admin')->user()->name ?? auth()->user()->name ?? 'Usuário' }}</p>
                            <p class="text-[10px] text-gray-400 truncate">{{ auth('admin')->user()->email ?? auth()->user()->email ?? '' }}</p>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- Main Content Area --}}
            <main class="flex-1 min-w-0 p-6 lg:p-8">
                @yield('content')
            </main>
        </div>

        {{-- Footer --}}
        <footer class="py-5 border-t border-gray-200 bg-white print:hidden">
            <div class="px-6 text-center">
                <p class="text-xs text-gray-400">
                    &copy; {{ date('Y') }} - <span class="font-semibold text-gray-500">Amura Sistemas</span>. Todos os direitos reservados.
                </p>
            </div>
        </footer>
    </div>

    @stack('scripts')
    <x-flash-toast />
    <x-confirm-modal />
</body>
</html>
