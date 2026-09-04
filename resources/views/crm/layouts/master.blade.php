<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CRM - Amura Sistemas')</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/amura-icon.png') }}?v=2">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Prevent FOUC (Flash of Unstyled Content) for Dark Mode -->
    <script>
        if (localStorage.getItem('theme') === 'ocean' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('ocean');
        } else {
            document.documentElement.classList.remove('ocean');
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }

        html.ocean aside section.bg-gray-50 {
            background-color: #0f172a !important;
            border-color: #334155 !important;
        }

        html.ocean aside section.bg-gray-50 > button:hover {
            background-color: #1e293b !important;
        }

        html.ocean aside .bg-gradient-to-r.from-teal-50.to-cyan-50 {
            background-image: none !important;
            background-color: #0f766e !important;
            border-color: #0d9488 !important;
            color: #ccfbf1 !important;
            box-shadow: none !important;
        }

        html.ocean aside .text-gray-600:hover {
            background-color: #1e293b !important;
            color: #e2e8f0 !important;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-800 transition-colors duration-300">

    @php
        $authUser = auth()->guard('admin')->user() ?? auth()->user();
        $isTicketitAdmin = (bool) ($authUser?->ticketit_admin);

        $sidebarSections = [
            'feedbacks' => [
                'active' => request()->routeIs('crm.index')
                    || request()->routeIs('feedback.create'),
            ],
        ];

        if ($isTicketitAdmin) {
            $sidebarSections['navegacao'] = [
                'active' => false,
            ];
        }

        $sidebarInitialOpen = [];
        foreach ($sidebarSections as $sectionKey => $sectionData) {
            $sidebarInitialOpen[$sectionKey] = (bool) $sectionData['active'];
        }

        if (!in_array(true, $sidebarInitialOpen, true) && !empty($sidebarInitialOpen)) {
            $sidebarInitialOpen[array_key_first($sidebarInitialOpen)] = true;
        }
    @endphp

    <div
        class="min-h-screen flex flex-col"
        x-data="{
            sidebarOpen: false,
            sidebarSections: @js($sidebarInitialOpen),
            toggleSidebarSection(sectionKey) {
                const isCurrentOpen = Boolean(this.sidebarSections[sectionKey]);
                Object.keys(this.sidebarSections).forEach((key) => {
                    this.sidebarSections[key] = false;
                });
                this.sidebarSections[sectionKey] = !isCurrentOpen;
            },
            isSidebarSectionOpen(sectionKey) {
                return Boolean(this.sidebarSections[sectionKey]);
            },
            handleSidebarLinkClick() {
                if (window.innerWidth < 1024) {
                    this.sidebarOpen = false;
                }
            }
        }"
    >

        {{-- Top Navbar --}}
        <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-200 shadow-sm transition-colors duration-300">
            <div class="px-4 lg:px-6 h-14 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    {{-- Mobile hamburger --}}
                    <button @click.stop="sidebarOpen = !sidebarOpen"
                            class="lg:hidden p-2 -ml-1 text-gray-500 hover:text-teal-600 hover:bg-teal-50 rounded-xl transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    {{-- Logo --}}
                    <a href="{{ route('crm.index') }}" class="flex items-center gap-2.5">
                        <img src="{{ asset('img/amura-logo-light.png') }}" alt="Amura" class="h-8 w-auto dark:hidden">
                        <img src="{{ asset('img/amura-logo-dark.png') }}" alt="Amura" class="h-8 w-auto hidden dark:block">
                        <span class="font-bold text-lg bg-clip-text text-transparent bg-gradient-to-r from-teal-600 to-cyan-600 tracking-tight hidden sm:inline ml-2 border-l border-gray-300 dark:border-gray-600 pl-2">CRM</span>
                    </a>
                </div>

                {{-- Right actions --}}
                <div class="flex items-center gap-4">
                    <div class="hidden md:block text-sm text-gray-500">
                        Bem-vindo, <span class="font-semibold text-gray-800">{{ $authUser?->name ?? 'Usuário' }}</span>
                    </div>
                    <div class="scale-90">
                        <x-theme-toggle />
                    </div>


                    <form method="POST" action="{{ route('admin.logout') }}" class="m-0 p-0">
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
                 class="fixed inset-0 bg-black/30 backdrop-blur-sm z-40 lg:hidden">
            </div>

            {{-- Sidebar --}}
            <aside class="fixed lg:sticky top-14 left-0 z-40 h-[calc(100vh-3.5rem)] w-60 bg-white border-r border-gray-100 overflow-y-auto transition-transform duration-300 ease-in-out lg:translate-x-0 flex flex-col shadow-sm"
                   :class="sidebarOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full'"
                   @click.outside="sidebarOpen = false">

                <nav class="flex-1 px-3 py-5 space-y-2">

                    <a href="{{ route('crm.index') }}" @click="handleSidebarLinkClick"
                       class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('crm.index') && !request('tab') ? 'bg-gradient-to-r from-teal-50 to-cyan-50 text-teal-700 font-semibold border border-teal-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border border-transparent' }}">
                        <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Dashboard
                    </a>

                    {{-- FEEDBACKS --}}
                    <section class="rounded-xl border border-gray-200 bg-gray-50">
                        <button type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left transition-colors hover:bg-gray-50"
                                @click="toggleSidebarSection('feedbacks')"
                                :aria-expanded="isSidebarSectionOpen('feedbacks')"
                                aria-controls="crm-sidebar-section-feedbacks">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Feedbacks</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                 :class="isSidebarSectionOpen('feedbacks') ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="crm-sidebar-section-feedbacks" x-show="isSidebarSectionOpen('feedbacks')" x-collapse x-cloak class="px-1 pb-2">
                            <div class="space-y-0.5">
                                <a href="{{ route('crm.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('crm.index') && request('tab') !== 'concluidos' ? 'bg-gradient-to-r from-teal-50 to-cyan-50 text-teal-700 font-semibold border border-teal-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                    Feedbacks Pendentes
                                </a>

                                <a href="{{ route('crm.index', ['tab' => 'concluidos']) }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request('tab') === 'concluidos' ? 'bg-gradient-to-r from-teal-50 to-cyan-50 text-teal-700 font-semibold border border-teal-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Concluídos
                                </a>

                                <a href="{{ route('feedback.create') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 {{ request()->routeIs('feedback.create') ? 'bg-gradient-to-r from-teal-50 to-cyan-50 text-teal-700 font-semibold border border-teal-100 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Novo Feedback
                                </a>
                            </div>
                        </div>
                    </section>

                    {{-- NAVEGAÇÃO --}}
                    @if($isTicketitAdmin)
                    <section class="rounded-xl border border-gray-200 bg-gray-50">
                        <button type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2 text-left transition-colors hover:bg-gray-50"
                                @click="toggleSidebarSection('navegacao')"
                                :aria-expanded="isSidebarSectionOpen('navegacao')"
                                aria-controls="crm-sidebar-section-navegacao">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Navegação</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                 :class="isSidebarSectionOpen('navegacao') ? 'rotate-180' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="crm-sidebar-section-navegacao" x-show="isSidebarSectionOpen('navegacao')" x-collapse x-cloak class="px-1 pb-2">
                            <div class="space-y-0.5">
                                <a href="{{ route('admin.dashboard') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 text-gray-600 hover:bg-gray-100 hover:text-gray-900 border border-dashed border-gray-200 hover:border-gray-300">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    Painel Admin
                                    <svg class="w-3 h-3 ml-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>

                                <a href="{{ route('agent.knowledge.index') }}" @click="handleSidebarLinkClick"
                                   class="flex items-center gap-3 px-3 py-2 rounded-xl text-[13px] font-medium transition-all duration-200 text-gray-600 hover:bg-amber-50 hover:text-amber-700 border border-dashed border-gray-200 hover:border-amber-300">
                                    <svg class="w-[18px] h-[18px] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    EasyWiki
                                    <svg class="w-3 h-3 ml-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </div>
                        </div>
                    </section>
                    @endif
                </nav>

                {{-- Sidebar Footer --}}
                <div class="px-4 py-3 border-t border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-teal-400 to-cyan-400 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                            {{ strtoupper(substr($authUser?->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-800 truncate">{{ $authUser?->name ?? 'Usuário' }}</p>
                            <p class="text-[10px] text-gray-400 truncate">{{ $authUser?->email ?? '' }}</p>
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
        <footer class="py-5 border-t border-gray-200 bg-white">
            <div class="px-6 text-center">
                <p class="text-xs text-gray-400">
                    &copy; {{ date('Y') }} - <span class="font-semibold text-gray-500">Amura Sistemas</span>. Todos os direitos reservados.
                </p>
            </div>
        </footer>
    </div>

    @yield('footer')
    @stack('scripts')
    <x-flash-toast />
    <x-confirm-modal />
</body>
</html>
