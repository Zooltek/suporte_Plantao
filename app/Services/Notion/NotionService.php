<?php

declare(strict_types=1);

namespace App\Services\Notion;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotionService
{
    private ?string $apiKey;
    private ?string $databaseId;
    private string $version;

    public function __construct()
    {
        // 1. Tenta carregar de ticketit_settings (banco de dados)
        $dbApiKey = null;
        $dbDatabaseId = null;
        $dbVersion = null;

        try {
            $settings = Cache::remember('ticketit_settings_notion', 300, function () {
                return DB::table('ticketit_settings')
                    ->whereIn('slug', ['notion_api_key', 'notion_database_id', 'notion_version'])
                    ->pluck('value', 'slug')
                    ->toArray();
            });

            $dbApiKey = $settings['notion_api_key'] ?? null;
            $dbDatabaseId = $settings['notion_database_id'] ?? null;
            $dbVersion = $settings['notion_version'] ?? null;
        } catch (\Throwable $e) {
            // Em caso de tabela inexistente em testes
        }

        $this->apiKey = $dbApiKey ?: (config('services.notion.api_key') ?: env('NOTION_API_KEY'));
        $this->databaseId = $dbDatabaseId ? self::cleanNotionId($dbDatabaseId) : (config('services.notion.database_id') ?: env('NOTION_DATABASE_ID'));
        $this->version = $dbVersion ?: (config('services.notion.version') ?: '2022-06-28');
    }

    public static function cleanNotionId(?string $input): ?string
    {
        if (empty($input)) {
            return null;
        }
        $input = trim($input);

        // Se for URL completa do Notion, extrai o identificador hexadecimal de 32 caracteres
        if (preg_match('/([a-f0-9]{32}|[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/i', $input, $matches)) {
            return str_replace('-', '', $matches[1]);
        }

        return str_replace('-', '', $input);
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function getSettings(): array
    {
        return [
            'api_key' => $this->apiKey,
            'masked_api_key' => $this->apiKey ? substr($this->apiKey, 0, 8) . '...' . substr($this->apiKey, -4) : '',
            'database_id' => $this->databaseId,
            'version' => $this->version,
            'is_configured' => $this->isConfigured(),
        ];
    }

    public function saveSettings(string $apiKey, ?string $databaseId = null, ?string $version = null): void
    {
        $cleanApiKey = trim($apiKey);
        $cleanDatabaseId = $databaseId !== null ? (self::cleanNotionId($databaseId) ?? '') : '';
        $cleanVersion = $version !== null ? trim($version) : '2022-06-28';

        DB::table('ticketit_settings')->updateOrInsert(
            ['slug' => 'notion_api_key'],
            ['value' => $cleanApiKey, 'default' => '']
        );

        DB::table('ticketit_settings')->updateOrInsert(
            ['slug' => 'notion_database_id'],
            ['value' => $cleanDatabaseId, 'default' => '']
        );

        DB::table('ticketit_settings')->updateOrInsert(
            ['slug' => 'notion_version'],
            ['value' => $cleanVersion, 'default' => '2022-06-28']
        );

        Cache::forget('ticketit_settings_notion');
        Cache::forget('notion_articles_all');
        Cache::forget('ticketit_settings');

        $this->apiKey = $cleanApiKey;
        $this->databaseId = $cleanDatabaseId !== '' ? $cleanDatabaseId : null;
        $this->version = $cleanVersion;
    }

    private function client(?string $customApiKey = null)
    {
        $token = $customApiKey ?: $this->apiKey;

        return Http::withToken($token)
            ->withHeaders([
                'Notion-Version' => $this->version,
                'Content-Type' => 'application/json',
            ])
            ->timeout(15);
    }

    /**
     * Testa a conexão com a API do Notion.
     */
    public function testConnection(?string $tempApiKey = null, ?string $tempDatabaseId = null): array
    {
        $token = $tempApiKey ? trim($tempApiKey) : $this->apiKey;

        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Token do Notion (API Key) não informado.',
            ];
        }

        try {
            $client = $this->client($token);
            $response = $client->get('https://api.notion.com/v1/users/me');

            if ($response->successful()) {
                $user = $response->json();
                $botName = $user['name'] ?? 'Integração Notion';

                // Tenta consultar páginas acessíveis para informar quantidade
                $searchCount = 0;
                try {
                    $searchRes = $client->post('https://api.notion.com/v1/search', ['page_size' => 100]);
                    if ($searchRes->successful()) {
                        $searchCount = count($searchRes->json('results') ?? []);
                    }
                } catch (\Throwable) {}

                return [
                    'success' => true,
                    'bot_name' => $botName,
                    'accessible_pages' => $searchCount,
                    'message' => "Conexão bem-sucedida com '{$botName}'! {$searchCount} documento(s) acessível(is).",
                ];
            }

            $errMsg = $response->json('message') ?? 'Token inválido ou sem permissão.';
            return [
                'success' => false,
                'status' => $response->status(),
                'message' => 'Notion retornou erro: ' . $errMsg,
            ];
        } catch (\Throwable $e) {
            Log::error('Erro ao conectar com Notion: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro de comunicação: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Lista artigos/páginas do Notion (Database, Página Raiz ou Busca Geral).
     */
    public function getArticles(?string $search = null): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $cacheKey = 'notion_articles_' . md5((string) $this->databaseId . '_' . (string) $search);

        return Cache::remember($cacheKey, now()->addMinutes(3), function () use ($search) {
            try {
                $articles = [];
                $fetched = false;

                // 1. Se informou um ID (seja Banco de Dados ou Página)
                if (!empty($this->databaseId)) {
                    $cleanId = $this->databaseId;

                    // 1.1 Tenta como Banco de Dados
                    $payload = ['page_size' => 100];
                    if (!empty($search)) {
                        $payload['filter'] = [
                            'or' => [
                                [
                                    'property' => 'title',
                                    'title' => ['contains' => $search],
                                ],
                            ],
                        ];
                    }

                    $response = $this->client()->post("https://api.notion.com/v1/databases/{$cleanId}/query", $payload);

                    if ($response->successful()) {
                        $results = $response->json('results') ?? [];
                        foreach ($results as $item) {
                            $article = $this->formatPageItem($item);
                            if ($article) {
                                $articles[] = $article;
                            }
                        }
                        $fetched = true;
                    }

                    // 1.2 Se falhou como banco de dados, tenta buscar como Página Raiz (subpáginas e blocos filhos)
                    if (!$fetched || empty($articles)) {
                        // Consulta se a página existe
                        $pageRes = $this->client()->get("https://api.notion.com/v1/pages/{$cleanId}");
                        if ($pageRes->successful()) {
                            $rootPage = $this->formatPageItem($pageRes->json());
                            if ($rootPage) {
                                $articles[] = $rootPage;
                            }
                        }

                        // Busca blocos filhos (child_page / child_database)
                        $blocksRes = $this->client()->get("https://api.notion.com/v1/blocks/{$cleanId}/children?page_size=100");
                        if ($blocksRes->successful()) {
                            $blocks = $blocksRes->json('results') ?? [];
                            foreach ($blocks as $block) {
                                if (($block['type'] ?? '') === 'child_page') {
                                    $childTitle = $block['child_page']['title'] ?? 'Página Sem Título';
                                    $articles[] = [
                                        'id' => $block['id'],
                                        'title' => $childTitle,
                                        'url' => 'https://www.notion.so/' . str_replace('-', '', $block['id']),
                                        'cover' => null,
                                        'icon' => '📄',
                                        'tags' => ['Subpágina'],
                                        'last_edited' => !empty($block['last_edited_time']) ? Carbon::parse($block['last_edited_time']) : null,
                                    ];
                                }
                            }
                            if (!empty($articles)) {
                                $fetched = true;
                            }
                        }
                    }
                }

                // 2. Se não encontrou por ID específico ou nenhum ID foi passado, busca tudo compartilhado
                if (!$fetched || empty($articles)) {
                    $searchPayload = ['page_size' => 100];
                    if (!empty($search)) {
                        $searchPayload['query'] = $search;
                    }

                    $searchResponse = $this->client()->post('https://api.notion.com/v1/search', $searchPayload);

                    if ($searchResponse->successful()) {
                        $results = $searchResponse->json('results') ?? [];
                        foreach ($results as $item) {
                            $article = $this->formatPageItem($item);
                            if ($article) {
                                $articles[] = $article;
                            }
                        }
                    } else {
                        Log::warning('Erro ao buscar páginas no Notion search:', [
                            'status' => $searchResponse->status(),
                            'body' => $searchResponse->body(),
                        ]);
                    }
                }

                // Filtro local por busca se foi buscado via search
                if (!empty($search)) {
                    $searchLower = mb_strtolower($search);
                    $articles = array_values(array_filter($articles, function ($a) use ($searchLower) {
                        return str_contains(mb_strtolower($a['title']), $searchLower);
                    }));
                }

                // Remove duplicatas por ID
                $unique = [];
                foreach ($articles as $a) {
                    $unique[$a['id']] = $a;
                }

                return array_values($unique);
            } catch (\Throwable $e) {
                Log::error('Exceção ao listar artigos do Notion: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Formata um objeto de página ou banco de dados do Notion em array padrão de artigo.
     */
    private function formatPageItem(array $page): ?array
    {
        $id = $page['id'] ?? null;
        if (!$id) {
            return null;
        }

        $title = $this->extractPageTitle($page);
        $cover = $this->extractPageCover($page);
        $icon = $this->extractPageIcon($page);
        $tags = $this->extractPageTags($page);
        $lastEdited = $page['last_edited_time'] ?? $page['created_time'] ?? null;

        $notionUrl = $page['url'] ?? ('https://www.notion.so/' . str_replace('-', '', $id));

        return [
            'id' => $id,
            'title' => $title,
            'url' => $notionUrl,
            'cover' => $cover,
            'icon' => $icon,
            'tags' => $tags,
            'last_edited' => $lastEdited ? Carbon::parse($lastEdited) : null,
        ];
    }

    /**
     * Retorna a estrutura em árvore de navegação (Sumário) do workspace / página raiz.
     */
    public function getNavigationTree(bool $refresh = false): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $cleanId = $this->databaseId;
        if (empty($cleanId)) {
            return [];
        }

        $cacheKey = 'notion_nav_tree_' . $cleanId;
        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($cleanId) {
            try {
                $rootPage = null;
                $pageRes = $this->client()->get("https://api.notion.com/v1/pages/{$cleanId}");
                if ($pageRes->successful()) {
                    $rootPage = $this->formatPageItem($pageRes->json());
                }

                $sections = [];
                $currentSection = [
                    'title' => 'Geral',
                    'items' => [],
                ];

                $blocksRes = $this->client()->get("https://api.notion.com/v1/blocks/{$cleanId}/children?page_size=100");
                if ($blocksRes->successful()) {
                    $blocks = $blocksRes->json('results') ?? [];

                    foreach ($blocks as $block) {
                        $type = $block['type'] ?? '';

                        if (in_array($type, ['heading_1', 'heading_2', 'heading_3'])) {
                            $headingText = trim($this->extractPlainText($block[$type]['rich_text'] ?? []));
                            if (!empty($headingText)) {
                                if (!empty($currentSection['items'])) {
                                    $sections[] = $currentSection;
                                }
                                $currentSection = [
                                    'title' => self::cleanTitleText($headingText),
                                    'items' => [],
                                ];
                            }
                        } elseif ($type === 'child_page') {
                            $subId = $block['id'] ?? '';
                            $title = self::cleanTitleText($block['child_page']['title'] ?? 'Subpágina');
                            $currentSection['items'][] = [
                                'id' => $subId,
                                'clean_id' => self::cleanNotionId($subId),
                                'title' => $title,
                                'icon' => '📄',
                                'url' => route('agent.knowledge.notion.show', $subId),
                            ];
                        }
                    }

                    if (!empty($currentSection['items'])) {
                        $sections[] = $currentSection;
                    }
                }

                // Fallback: se não encontrou seções com child_page na página raiz, usa os artigos gerais do Notion
                if (empty($sections)) {
                    $allArticles = $this->getArticles();
                    $items = [];
                    foreach ($allArticles as $art) {
                        if (self::cleanNotionId($art['id']) === $cleanId) {
                            continue;
                        }
                        $items[] = [
                            'id' => $art['id'],
                            'clean_id' => self::cleanNotionId($art['id']),
                            'title' => self::cleanTitleText($art['title']),
                            'icon' => $art['icon'] ?: '📄',
                            'url' => route('agent.knowledge.notion.show', $art['id']),
                        ];
                    }
                    if (!empty($items)) {
                        $sections[] = [
                            'title' => 'Documentos',
                            'items' => $items,
                        ];
                    }
                }

                $rootTitle = $rootPage['title'] ?? 'EasyWiki';
                return [
                    'root' => [
                        'id' => $cleanId,
                        'clean_id' => $cleanId,
                        'title' => self::cleanTitleText($rootTitle),
                        'icon' => $rootPage['icon'] ?? '🏷️',
                        'url' => route('agent.knowledge.notion.show', $cleanId),
                    ],
                    'sections' => $sections,
                ];
            } catch (\Throwable $e) {
                Log::warning('[NotionService] Erro ao construir NavigationTree: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Recupera uma página e seus blocos convertidos em HTML.
     */
    public function getArticle(string $pageId): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $cleanId = self::cleanNotionId($pageId) ?? $pageId;
        $cacheKey = 'notion_page_' . $cleanId;

        return Cache::remember($cacheKey, now()->addMinutes(3), function () use ($cleanId) {
            try {
                $pageRes = $this->client()->get("https://api.notion.com/v1/pages/{$cleanId}");
                $page = $pageRes->successful() ? $pageRes->json() : null;

                // Se não for página, tenta consultar como banco de dados
                if (!$page) {
                    $dbRes = $this->client()->get("https://api.notion.com/v1/databases/{$cleanId}");
                    if ($dbRes->successful()) {
                        $page = $dbRes->json();
                    }
                }

                if (!$page) {
                    Log::warning("Página do Notion {$cleanId} não encontrada.", ['status' => $pageRes?->status()]);
                    return null;
                }

                $title = $this->extractPageTitle($page);
                $cover = $this->extractPageCover($page);
                $icon = $this->extractPageIcon($page);
                $tags = $this->extractPageTags($page);
                $lastEdited = $page['last_edited_time'] ?? $page['created_time'] ?? null;
                $notionUrl = $page['url'] ?? ('https://www.notion.so/' . str_replace('-', '', $cleanId));

                // Busca os blocos da página
                $blocksRes = $this->client()->get("https://api.notion.com/v1/blocks/{$cleanId}/children?page_size=100");
                $blocks = $blocksRes->successful() ? ($blocksRes->json('results') ?? []) : [];

                // Se o título for genérico, URL ou anexo, tenta pegar o primeiro Heading do corpo do artigo
                if (empty($title) || $title === 'Documento / Anexo Notion' || $title === 'Artigo sem título' || preg_match('#^https?://#i', $title)) {
                    foreach ($blocks as $block) {
                        $bType = $block['type'] ?? '';
                        if (in_array($bType, ['heading_1', 'heading_2', 'heading_3'])) {
                            $headingText = $this->extractPlainText($block[$bType]['rich_text'] ?? []);
                            if (!empty(trim($headingText))) {
                                $title = self::cleanTitleText(trim($headingText));
                                break;
                            }
                        }
                    }
                }

                $htmlContent = $this->renderBlocksToHtml($blocks);

                if (empty(trim($htmlContent))) {
                    $htmlContent = '<p class="text-gray-500 italic">Esta página não possui conteúdo de texto adicional.</p>';
                }

                return [
                    'id' => $cleanId,
                    'title' => $title,
                    'url' => $notionUrl,
                    'cover' => $cover,
                    'icon' => $icon,
                    'tags' => $tags,
                    'last_edited' => $lastEdited ? Carbon::parse($lastEdited) : null,
                    'html' => $htmlContent,
                ];
            } catch (\Throwable $e) {
                Log::error('Erro ao buscar página no Notion: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Converte blocos do Notion em HTML limpo e estilizado.
     */
    public function renderBlocksToHtml(array $blocks): string
    {
        $html = '';
        $inBulletedList = false;
        $inNumberedList = false;
        $inChildPageGroup = false;

        foreach ($blocks as $block) {
            $type = $block['type'] ?? '';

            // Fecha lista não ordenada se o bloco atual não for item de lista
            if ($type !== 'bulleted_list_item' && $inBulletedList) {
                $html .= '</ul>';
                $inBulletedList = false;
            }

            // Fecha lista ordenada
            if ($type !== 'numbered_list_item' && $inNumberedList) {
                $html .= '</ol>';
                $inNumberedList = false;
            }

            // Fecha grupo de páginas filhas (sumário) se o bloco atual não for child_page
            if ($type !== 'child_page' && $inChildPageGroup) {
                $html .= '</div>';
                $inChildPageGroup = false;
            }

            switch ($type) {
                case 'paragraph':
                    $text = $this->renderRichText($block['paragraph']['rich_text'] ?? []);
                    if (!empty(trim($text))) {
                        $html .= "<p class=\"my-3 text-gray-800 dark:text-slate-200 leading-relaxed text-sm sm:text-base\">{$text}</p>";
                    } else {
                        $html .= '<div class="h-2"></div>';
                    }
                    break;

                case 'heading_1':
                    $text = $this->renderRichText($block['heading_1']['rich_text'] ?? []);
                    $html .= "<h1 class=\"text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white mt-8 mb-3 pb-2 border-b border-gray-100 dark:border-slate-800\">{$text}</h1>";
                    break;

                case 'heading_2':
                    $text = $this->renderRichText($block['heading_2']['rich_text'] ?? []);
                    $html .= "<h2 class=\"text-xl sm:text-2xl font-bold text-gray-900 dark:text-slate-100 mt-6 mb-2\">{$text}</h2>";
                    break;

                case 'heading_3':
                    $text = $this->renderRichText($block['heading_3']['rich_text'] ?? []);
                    $html .= "<h3 class=\"text-base sm:text-lg font-bold text-gray-800 dark:text-slate-200 mt-4 mb-1.5\">{$text}</h3>";
                    break;

                case 'bulleted_list_item':
                    if (!$inBulletedList) {
                        $html .= '<ul class="list-disc list-inside space-y-1.5 my-3 text-sm sm:text-base text-gray-800 dark:text-slate-200 pl-2">';
                        $inBulletedList = true;
                    }
                    $text = $this->renderRichText($block['bulleted_list_item']['rich_text'] ?? []);
                    $html .= "<li>{$text}</li>";
                    break;

                case 'numbered_list_item':
                    if (!$inNumberedList) {
                        $html .= '<ol class="list-decimal list-inside space-y-1.5 my-3 text-sm sm:text-base text-gray-800 dark:text-slate-200 pl-2">';
                        $inNumberedList = true;
                    }
                    $text = $this->renderRichText($block['numbered_list_item']['rich_text'] ?? []);
                    $html .= "<li>{$text}</li>";
                    break;

                case 'to_do':
                    $checked = !empty($block['to_do']['checked']);
                    $text = $this->renderRichText($block['to_do']['rich_text'] ?? []);
                    $checkIcon = $checked
                        ? '<span class="text-emerald-600 dark:text-emerald-400 font-bold">☑</span>'
                        : '<span class="text-gray-400 font-bold">☐</span>';
                    $strikeClass = $checked ? 'line-through text-gray-400 dark:text-slate-500' : 'text-gray-800 dark:text-slate-200';
                    $html .= "<div class=\"flex items-start gap-2 my-1 text-sm sm:text-base {$strikeClass}\">{$checkIcon} <span>{$text}</span></div>";
                    break;

                case 'toggle':
                    $text = $this->renderRichText($block['toggle']['rich_text'] ?? []);
                    $html .= "<details class=\"my-3 p-3 bg-gray-50 dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700\"><summary class=\"font-bold text-gray-900 dark:text-white cursor-pointer select-none\">{$text}</summary></details>";
                    break;

                case 'child_page':
                    if (!$inChildPageGroup) {
                        $html .= '<div class="my-4 rounded-2xl border border-slate-200 dark:border-slate-700/80 bg-slate-50/60 dark:bg-slate-800/40 divide-y divide-slate-200/80 dark:divide-slate-700/60 overflow-hidden shadow-xs">';
                        $inChildPageGroup = true;
                    }
                    $title = htmlspecialchars(self::cleanTitleText($block['child_page']['title'] ?? 'Subpágina'));
                    $subId = $block['id'] ?? '';
                    $route = route('agent.knowledge.notion.show', $subId);
                    $html .= "<a href=\"{$route}\" class=\"group flex items-center justify-between px-4 py-3 hover:bg-indigo-50/80 dark:hover:bg-slate-700/60 transition-all text-slate-800 dark:text-slate-200\">
                        <div class=\"flex items-center gap-3 min-w-0\">
                            <span class=\"text-base text-slate-400 dark:text-slate-500 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors flex-shrink-0\">📄</span>
                            <span class=\"text-sm font-semibold group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors truncate\">{$title}</span>
                        </div>
                        <div class=\"flex items-center gap-1.5 text-xs text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 font-bold transition-all flex-shrink-0\">
                            <span class=\"text-[11px] opacity-0 group-hover:opacity-100 -translate-x-1 group-hover:translate-x-0 transition-all\">Abrir</span>
                            <span>→</span>
                        </div>
                    </a>";
                    break;

                case 'callout':
                    $text = $this->renderRichText($block['callout']['rich_text'] ?? []);
                    $icon = $block['callout']['icon']['emoji'] ?? '💡';
                    $html .= "<div class=\"flex items-start gap-3 p-4 my-4 bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-800 rounded-xl text-sm text-indigo-950 dark:text-indigo-200\"><span class=\"text-xl flex-shrink-0\">{$icon}</span><div>{$text}</div></div>";
                    break;

                case 'quote':
                    $text = $this->renderRichText($block['quote']['rich_text'] ?? []);
                    $html .= "<blockquote class=\"border-l-4 border-indigo-400 pl-4 py-1 my-4 italic text-gray-700 dark:text-slate-300 bg-gray-50/50 dark:bg-slate-800/40 text-sm sm:text-base rounded-r-lg\">{$text}</blockquote>";
                    break;

                case 'code':
                    $text = htmlspecialchars($this->extractPlainText($block['code']['rich_text'] ?? []));
                    $lang = htmlspecialchars($block['code']['language'] ?? 'text');
                    $html .= "<div class=\"my-4 rounded-xl overflow-hidden border border-slate-700 bg-slate-900 text-slate-100\"><div class=\"px-4 py-1.5 bg-slate-800 text-[11px] font-mono text-slate-400 uppercase\">{$lang}</div><pre class=\"p-4 text-xs font-mono overflow-x-auto leading-relaxed\"><code>{$text}</code></pre></div>";
                    break;

                case 'image':
                    $imgUrl = $block['image']['file']['url'] ?? $block['image']['external']['url'] ?? '';
                    if ($imgUrl) {
                        $caption = $this->renderRichText($block['image']['caption'] ?? []);
                        $captionHtml = $caption ? "<figcaption class=\"text-center text-xs text-gray-400 dark:text-slate-500 mt-1.5\">{$caption}</figcaption>" : '';
                        $html .= "<figure class=\"my-5\"><img src=\"" . htmlspecialchars($imgUrl) . "\" alt=\"Notion Image\" class=\"max-w-full rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm mx-auto object-contain max-h-[500px]\">{$captionHtml}</figure>";
                    }
                    break;

                case 'file':
                case 'pdf':
                    $fileUrl = $block[$type]['file']['url'] ?? $block[$type]['external']['url'] ?? '';
                    if ($fileUrl) {
                        $rawName = $block[$type]['name'] ?? '';
                        if (empty($rawName)) {
                            $path = parse_url($fileUrl, PHP_URL_PATH) ?? '';
                            $rawName = urldecode(basename($path));
                        }
                        $cleanName = self::cleanTitleText($rawName ?: 'Arquivo Anexado');
                        $caption = $this->renderRichText($block[$type]['caption'] ?? []);
                        $captionHtml = $caption ? "<p class=\"text-xs text-gray-500 dark:text-slate-400 mt-1\">{$caption}</p>" : '';
                        $html .= "<div class=\"my-4 p-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3\"><div class=\"flex items-center gap-3 min-w-0\"><span class=\"text-2xl flex-shrink-0\">📎</span><div class=\"min-w-0\"><p class=\"font-bold text-sm text-gray-900 dark:text-white truncate\">" . htmlspecialchars($cleanName) . "</p>{$captionHtml}</div></div><a href=\"" . htmlspecialchars($fileUrl) . "\" target=\"_blank\" class=\"inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all flex-shrink-0\"><span>Abrir / Baixar</span><svg class=\"w-3.5 h-3.5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14\"/></svg></a></div>";
                    }
                    break;

                case 'bookmark':
                case 'link_preview':
                case 'link_to_page':
                case 'embed':
                    $embedUrl = $block[$type]['url'] ?? '';
                    if ($embedUrl) {
                        $caption = $this->renderRichText($block[$type]['caption'] ?? []);
                        $cleanUrlTitle = self::cleanTitleText($embedUrl);
                        $display = $caption ?: $cleanUrlTitle;
                        $html .= "<div class=\"my-3 p-3.5 rounded-xl border border-indigo-100 dark:border-indigo-900/60 bg-indigo-50/50 dark:bg-indigo-950/30 flex items-center justify-between gap-2\"><a href=\"" . htmlspecialchars($embedUrl) . "\" target=\"_blank\" class=\"flex items-center gap-2 text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline min-w-0 truncate\"><span>🔗</span> <span class=\"truncate\">" . htmlspecialchars($display) . "</span></a><span class=\"text-xs text-indigo-500 font-bold flex-shrink-0\">↗</span></div>";
                    }
                    break;

                case 'video':
                    $videoUrl = $block['video']['file']['url'] ?? $block['video']['external']['url'] ?? '';
                    if ($videoUrl) {
                        $caption = $this->renderRichText($block['video']['caption'] ?? []);
                        $captionHtml = $caption ? "<figcaption class=\"text-center text-xs text-gray-400 dark:text-slate-500 mt-1.5\">{$caption}</figcaption>" : '';

                        // Verifica se é YouTube
                        if (preg_match('#(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([a-zA-Z0-9_\-]+)#i', $videoUrl, $ytMatches)) {
                            $embedSrc = "https://www.youtube.com/embed/{$ytMatches[1]}";
                            $html .= "<figure class=\"my-6\"><div class=\"relative w-full aspect-video rounded-2xl overflow-hidden shadow-md border border-gray-200 dark:border-slate-700 bg-black\"><iframe src=\"{$embedSrc}\" class=\"w-full h-full\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe></div>{$captionHtml}</figure>";
                        }
                        // Verifica se é Loom
                        elseif (preg_match('#loom\.com/(?:share|embed)/([a-zA-Z0-9]+)#i', $videoUrl, $loomMatches)) {
                            $embedSrc = "https://www.loom.com/embed/{$loomMatches[1]}";
                            $html .= "<figure class=\"my-6\"><div class=\"relative w-full aspect-video rounded-2xl overflow-hidden shadow-md border border-gray-200 dark:border-slate-700 bg-black\"><iframe src=\"{$embedSrc}\" class=\"w-full h-full\" frameborder=\"0\" allowfullscreen></iframe></div>{$captionHtml}</figure>";
                        }
                        // Verifica se é Vimeo
                        elseif (preg_match('#vimeo\.com/(?:video/)?([0-9]+)#i', $videoUrl, $vimeoMatches)) {
                            $embedSrc = "https://player.vimeo.com/video/{$vimeoMatches[1]}";
                            $html .= "<figure class=\"my-6\"><div class=\"relative w-full aspect-video rounded-2xl overflow-hidden shadow-md border border-gray-200 dark:border-slate-700 bg-black\"><iframe src=\"{$embedSrc}\" class=\"w-full h-full\" frameborder=\"0\" allow=\"autoplay; fullscreen; picture-in-picture\" allowfullscreen></iframe></div>{$captionHtml}</figure>";
                        }
                        // Vídeo direto (upload no Notion / AWS S3 / .mp4 / .webm)
                        else {
                            $html .= "<figure class=\"my-6\"><video controls preload=\"metadata\" class=\"w-full rounded-2xl border border-gray-200 dark:border-slate-700 shadow-md max-h-[550px] bg-black\"><source src=\"" . htmlspecialchars($videoUrl) . "\">Seu navegador não suporta a tag de vídeo.</video>{$captionHtml}</figure>";
                        }
                    }
                    break;

                case 'audio':
                    $audioUrl = $block['audio']['file']['url'] ?? $block['audio']['external']['url'] ?? '';
                    if ($audioUrl) {
                        $html .= "<div class=\"my-4 p-3 rounded-xl border border-gray-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40\"><audio controls class=\"w-full\"><source src=\"" . htmlspecialchars($audioUrl) . "\">Seu navegador não suporta áudio.</audio></div>";
                    }
                    break;

                case 'divider':
                    $html .= '<hr class="my-6 border-gray-200 dark:border-slate-700">';
                    break;
            }
        }

        if ($inBulletedList) $html .= '</ul>';
        if ($inNumberedList) $html .= '</ol>';
        if ($inChildPageGroup) $html .= '</div>';

        return $html;
    }

    private function renderRichText(array $richText): string
    {
        $rendered = '';
        foreach ($richText as $chunk) {
            $plain = htmlspecialchars($chunk['plain_text'] ?? '');
            $annotations = $chunk['annotations'] ?? [];

            if (!empty($annotations['bold'])) $plain = "<strong>{$plain}</strong>";
            if (!empty($annotations['italic'])) $plain = "<em>{$plain}</em>";
            if (!empty($annotations['strikethrough'])) $plain = "<del>{$plain}</del>";
            if (!empty($annotations['underline'])) $plain = "<u>{$plain}</u>";
            if (!empty($annotations['code'])) $plain = "<code class=\"px-1.5 py-0.5 bg-gray-100 dark:bg-slate-800 text-pink-600 dark:text-pink-400 rounded text-xs font-mono\">{$plain}</code>";

            if (!empty($chunk['href'])) {
                $href = htmlspecialchars($chunk['href']);
                $plain = "<a href=\"{$href}\" target=\"_blank\" class=\"text-indigo-600 dark:text-indigo-400 underline hover:text-indigo-800 dark:hover:text-indigo-300\">{$plain}</a>";
            }

            $rendered .= $plain;
        }

        return $rendered;
    }

    private function extractPlainText(array $richText): string
    {
        $text = '';
        foreach ($richText as $chunk) {
            $text .= $chunk['plain_text'] ?? '';
        }
        return $text;
    }

    /**
     * Limpa títulos de páginas/artigos que sejam URLs brutas da AWS S3 ou links de arquivos.
     */
    public static function cleanTitleText(string $rawTitle): string
    {
        $title = trim($rawTitle);
        if (empty($title)) {
            return 'Artigo sem título';
        }

        // Se o título for uma URL (S3, Notion attachment, cloud storage ou URL web)
        if (preg_match('#^https?://#i', $title)) {
            $path = parse_url($title, PHP_URL_PATH) ?? '';
            $filename = urldecode(basename($path));

            // Se tiver um nome de arquivo identificável (não apenas um UUID/hash)
            if (!empty($filename) && !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $filename) && !preg_match('/^[0-9a-f]{20,}$/i', $filename)) {
                $cleanName = pathinfo($filename, PATHINFO_FILENAME);
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $cleanName = str_replace(['_', '-', '+', '%20'], ' ', $cleanName);
                $cleanName = ucwords(trim($cleanName));

                if (!empty($ext) && strlen($ext) <= 5) {
                    return $cleanName . ' (' . strtoupper($ext) . ')';
                }
                return $cleanName;
            }

            // Se for URL da AWS S3 do Notion sem nome específico
            if (str_contains($title, 'amazonaws.com') || str_contains($title, 'notion-static') || str_contains($title, 'prod-files-secure')) {
                return 'Documento / Anexo Notion';
            }

            // Outras URLs genéricas
            $host = parse_url($title, PHP_URL_HOST) ?? 'Link Externo';
            return 'Link: ' . $host;
        }

        return $title;
    }

    private function extractPageTitle(array $page): string
    {
        // 1. Tenta título direto do objeto (database ou page)
        if (!empty($page['title']) && is_array($page['title'])) {
            $text = $this->extractPlainText($page['title']);
            if (!empty(trim($text))) {
                return self::cleanTitleText($text);
            }
        }

        // 2. Tenta pelas propriedades da página
        $props = $page['properties'] ?? [];

        foreach ($props as $key => $prop) {
            if (($prop['type'] ?? '') === 'title' && !empty($prop['title'])) {
                $text = $this->extractPlainText($prop['title']);
                if (!empty(trim($text))) {
                    return self::cleanTitleText($text);
                }
            }
        }

        // 3. Tenta propriedade com nome 'title', 'Name', 'Nome', 'Título', 'Assunto', 'Documento'
        foreach (['title', 'Name', 'Nome', 'Título', 'Assunto', 'Page', 'Documento'] as $key) {
            if (isset($props[$key])) {
                $val = $props[$key];
                if (($val['type'] ?? '') === 'title' && !empty($val['title'])) {
                    $text = $this->extractPlainText($val['title']);
                    if (!empty(trim($text))) {
                        return self::cleanTitleText($text);
                    }
                } elseif (($val['type'] ?? '') === 'rich_text' && !empty($val['rich_text'])) {
                    $text = $this->extractPlainText($val['rich_text']);
                    if (!empty(trim($text))) {
                        return self::cleanTitleText($text);
                    }
                }
            }
        }

        // 4. Fallback com ID curto
        $id = $page['id'] ?? '';
        return !empty($id) ? ('Documento Notion (' . substr(str_replace('-', '', $id), 0, 8) . ')') : 'Artigo sem título';
    }

    private function extractPageCover(array $page): ?string
    {
        return $page['cover']['file']['url'] ?? $page['cover']['external']['url'] ?? null;
    }

    private function extractPageIcon(array $page): ?string
    {
        return $page['icon']['emoji'] ?? $page['icon']['file']['url'] ?? $page['icon']['external']['url'] ?? null;
    }

    private function extractPageTags(array $page): array
    {
        $props = $page['properties'] ?? [];

        foreach ($props as $prop) {
            if (($prop['type'] ?? '') === 'multi_select') {
                return array_column($prop['multi_select'] ?? [], 'name');
            }
        }

        return [];
    }
}
