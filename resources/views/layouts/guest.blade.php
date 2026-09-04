<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Suporte') }}</title>

        <link rel="icon" href="{{ asset('img/amura-icon.png') }}?v=2" type="image/png">

        <script>
            if (localStorage.getItem('theme') === 'ocean' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('ocean');
            } else {
                document.documentElement.classList.remove('ocean');
            }
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            html,
            body {
                min-height: 100%;
                height: 100%;
            }

            body.guest-auth-shell {
                min-height: 100dvh;
            }

            .auth-main {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100dvh;
                padding: 1rem 0.75rem;
            }

            .auth-footer {
                position: fixed;
                left: 0;
                right: 0;
                bottom: 1rem;
                padding: 0 1rem;
                pointer-events: none;
            }

            html.ocean body.guest-auth-shell {
                background-color: #0f172a !important;
                background-image:
                    radial-gradient(circle at top center, rgba(249, 115, 22, 0.08), transparent 28%),
                    radial-gradient(circle at top right, rgba(56, 189, 248, 0.06), transparent 22%) !important;
            }

            html.ocean .auth-backdrop-top {
                opacity: 0.18;
            }

            html.ocean .auth-backdrop-bottom {
                display: none;
            }

            html.ocean .auth-card {
                background-color: rgba(15, 23, 42, 0.92) !important;
                border-color: #334155 !important;
                box-shadow: 0 20px 45px rgba(2, 6, 23, 0.45) !important;
            }

            @media (max-height: 760px) and (min-width: 821px) {
                .auth-card {
                    padding: 1.375rem 1.25rem;
                }

                .auth-footer-runtime {
                    display: none;
                }
            }

            @media (max-height: 700px) and (max-width: 820px) {
                .auth-card {
                    padding: 1.5rem 1.25rem;
                }

                .auth-main {
                    min-height: auto;
                    align-items: flex-start;
                    padding-top: 1rem;
                    padding-bottom: 1rem;
                }

                .auth-footer {
                    position: static;
                    margin-top: 0.75rem;
                    gap: 0.25rem;
                }

                .auth-footer-runtime {
                    display: none;
                }
            }

            @media (max-height: 620px) and (max-width: 820px) {
                .auth-card {
                    padding: 1rem 0.875rem;
                }

                .auth-main {
                    padding-top: 0.75rem;
                }

                .login-header {
                    margin-bottom: 1rem;
                }

                .login-subtitle {
                    display: none;
                }
            }
        </style>
    </head>
    <body class="guest-auth-shell relative overflow-x-hidden bg-gray-50 font-sans text-gray-900 antialiased transition-colors duration-300 dark:bg-slate-900">
        <div class="relative flex min-h-[100dvh] flex-col overflow-x-hidden">

            {{-- Decorative Elements Wrapper --}}
            <div class="pointer-events-none absolute inset-0 z-0 overflow-hidden">
                <div class="auth-backdrop-top absolute -top-24 -right-24 h-96 w-96 rounded-full bg-gradient-to-br from-orange-400/20 to-rose-400/20 blur-3xl"></div>
                <div class="auth-backdrop-bottom absolute -bottom-24 -left-24 h-96 w-96 rounded-full bg-gradient-to-tr from-blue-400/10 to-indigo-400/10 blur-3xl"></div>
            </div>

            <div class="fixed right-3 top-3 z-20 sm:right-5 sm:top-5">
                <x-theme-toggle />
            </div>

            <main class="auth-main relative z-10 sm:px-6">
                <div class="w-full max-w-md">
                    <div class="auth-card overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 py-6 shadow-xl transition-all duration-300 sm:px-8 sm:py-10 dark:border-slate-700 dark:bg-slate-800">
                        {{ $slot }}
                    </div>
                </div>
            </main>

            <footer class="auth-footer relative z-10 text-center sm:pb-0">
                <div class="flex flex-col items-center gap-2">
                    <span class="text-[10px] font-semibold tracking-[0.12em] text-gray-500 dark:text-gray-400 sm:text-xs sm:uppercase sm:tracking-widest">
                        <span class="sm:hidden">&copy; {{ date('Y') }} · Amura Sistemas</span>
                        <span class="hidden sm:inline">&copy; {{ date('Y') }} - Amura Sistemas. Todos os direitos reservados.</span>
                    </span>
                    <span class="auth-footer-runtime text-[11px] font-mono text-gray-400 dark:text-gray-500">
                        Laravel v{{ app()->version() }} (PHP v{{ PHP_VERSION }})
                    </span>
                </div>
            </footer>
        </div>
        <x-flash-toast />
    </body>
</html>
