<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manutenção - {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Lato', sans-serif;
            background-color: #f8f9fa;
            color: #636b6f;
        }
        .maintenance-title {
            font-size: calc(1.5rem + 3vw); /* Responsividade melhor que 4rem fixo */
            font-weight: 300;
        }
        /* Melhoria para acessibilidade da barra de progresso nativa */
        progress {
            width: 100%;
            height: 8px;
        }
    </style>
</head>
<body class="d-flex align-items-center vh-100">
    {{-- Uso da tag <main> em vez de <div> para semântica (Sonar S6819) --}}
    <main class="container text-center">
        <div class="row justify-content-center">
            <article class="col-md-8">
                {{-- Ícone com aria-hidden pois é apenas decorativo --}}
                <div class="mb-4">
                    <i class="fas fa-tools fa-4x text-warning" aria-hidden="true"></i>
                </div>
                
                <h1 class="maintenance-title mb-3">Voltamos logo!</h1>
                
                <p class="lead mb-4">
                    Estamos realizando uma atualização importante no módulo de
                    <strong>{{ $module_name ?? 'Sistema' }}</strong> para o Laravel 12.
                </p>

                {{-- Substituição da div role="progressbar" pela tag nativa <progress> --}}
                <div class="mb-4">
                    <label for="maintenance-progress" class="sr-only">Progresso da manutenção:</label>
                    <progress id="maintenance-progress" value="75" max="100" class="w-100">75%</progress>
                </div>

                <p class="text-muted small">
                    Agradecemos a sua paciência.
                </p>
            </article>
        </div>
    </main>
</body>
</html>
