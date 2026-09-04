<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Feedback Atualizado</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 antialiased min-h-screen flex items-center justify-center p-4">

    <main class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 w-full max-w-sm text-center">
        
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
            <i class="fa-solid fa-check text-3xl text-green-600"></i>
        </div>

        <h1 class="text-xl font-bold text-gray-900 mb-2">
            Tudo certo!
        </h1>

        <p class="text-gray-600 text-base mb-6">
            Feedback atualizado com sucesso.
        </p>

        <div class="flex items-center justify-center gap-2 text-xs text-gray-400">
            <i class="fa-solid fa-spinner fa-spin"></i>
            <span>Fechando janela...</span>
        </div>

    </main>

    <script>
        const redirectUrl = @json($redirectUrl ?? route('crm.index'));

        setTimeout(() => {
            if (window.opener && !window.opener.closed) {
                try {
                    window.opener.location.href = redirectUrl;
                } catch (e) {
                    // Ignore cross-window access issues and keep fallback behavior.
                }
                window.close();
                return;
            }

            window.location.href = redirectUrl;
        }, 2000);
    </script>
</body>
</html>
