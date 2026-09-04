<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $project->name }} - Lista de Modificações</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('favicon.ico') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased selection:bg-blue-200">

    <main class="max-w-[1100px] mx-auto p-4 md:p-6 lg:p-8">
        
        <header class="mb-8 border-b border-gray-200 pb-4 flex justify-between items-end">
            <h1 class="text-2xl font-light text-gray-600 uppercase tracking-wide">
                <span class="font-bold text-gray-900">{{ $project->name }}</span> - Lista de Modificações
            </h1>
            
            <button onclick="window.print()" class="hidden md:flex items-center gap-2 text-sm text-gray-500 hover:text-blue-600 transition-colors">
                <i class="fa-solid fa-print"></i> Imprimir
            </button>
        </header>

        <div class="space-y-6">
            @forelse($versions as $version)
                <section x-data="{ open: true }" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    
                    <button @click="open = !open" class="w-full flex items-center justify-between p-5 bg-gray-50/50 hover:bg-gray-50 transition-colors focus:outline-none">
                        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-3">
                            <i class="fa-solid fa-code-branch text-blue-500"></i>
                            {{ $version->reference_date->format('d/m/Y') }} - {{ $version->name }}
                        </h2>
                        <i class="fa-solid fa-chevron-down text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                    </button>

                    <div x-show="open" x-collapse x-cloak class="p-5 border-t border-gray-100">
                        <div class="space-y-2 pl-2">
                            @foreach($version->changelogs as $changelog)
                                @if($changelog->blank)
                                    <div class="h-4"></div> 
                                @else
                                    <p class="text-sm leading-relaxed {{ $changelog->bold ? 'font-bold' : 'font-normal' }}"
                                       style="{{ $changelog->color ? 'color: '.$changelog->color.';' : 'color: #374151;' }}">
                                        
                                        @if(!$changelog->title)
                                            <span class="text-gray-400 mr-2 opacity-70">-</span>
                                        @endif 
                                        
                                        {{ $changelog->content }}
                                    </p>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </section>
            @empty
                <div class="text-center py-12 bg-white rounded-lg border border-dashed border-gray-300">
                    <i class="fa-regular fa-folder-open text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">Nenhuma modificação registrada para este projeto.</p>
                </div>
            @endforelse
        </div>
    </main>

    <style>
        [x-cloak] { display: none !important; }
        
        @media print {
            body { background: white; }
            section { border: none; box-shadow: none; margin-bottom: 2rem; }
            button { display: none !important; }
            [x-show] { display: block !important; height: auto !important; }
        }
    </style>
</body>
</html>