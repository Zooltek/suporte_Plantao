@extends('layouts.agent')

@section('title', 'Suporte - Central de Ajuda, Manual & Atualizações')

@section('content')
<div class="max-w-6xl mx-auto space-y-8 animate-fade-in-up" x-data="{ 
    activeTab: 'manual',
    manualSearch: '',
    expandedChapter: 4,
    matchesSearch(text) {
        if (!this.manualSearch.trim()) return true;
        return text.toLowerCase().includes(this.manualSearch.toLowerCase());
    }
}">
    
    {{-- Header Content --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-200 dark:border-slate-700 pb-6 relative">
        <div class="absolute -top-10 -left-10 w-48 h-48 bg-orange-400/10 blur-3xl rounded-full pointer-events-none"></div>

        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-50 dark:bg-orange-950/60 text-orange-700 dark:text-orange-400 text-xs font-bold uppercase tracking-wider mb-2 border border-orange-200/60 dark:border-orange-800/60">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                Base Oficial v2.0
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 dark:text-slate-100 tracking-tight">Central de Ajuda & Manual</h1>
            <p class="mt-1 text-gray-600 dark:text-slate-400 text-sm md:text-base">Documentação completa, guia de atalhos e histórico de versões do Sistema Amura.</p>
        </div>

        {{-- Tab Navigation --}}
        <div class="flex p-1.5 space-x-1.5 bg-gray-100/90 dark:bg-slate-800/80 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-inner">
            <button @click="activeTab = 'manual'" 
                    :class="{ 'bg-white dark:bg-slate-700 shadow text-orange-600 dark:text-orange-400 font-bold': activeTab === 'manual', 'text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-100 font-medium': activeTab !== 'manual' }"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm rounded-xl transition-all focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                Manual do Usuário
            </button>
            <button @click="activeTab = 'hotkeys'" 
                    :class="{ 'bg-white dark:bg-slate-700 shadow text-orange-600 dark:text-orange-400 font-bold': activeTab === 'hotkeys', 'text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-100 font-medium': activeTab !== 'hotkeys' }"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm rounded-xl transition-all focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                Teclas de Atalho
            </button>
            <button @click="activeTab = 'changelog'" 
                    :class="{ 'bg-white dark:bg-slate-700 shadow text-orange-600 dark:text-orange-400 font-bold': activeTab === 'changelog', 'text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-100 font-medium': activeTab !== 'changelog' }"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm rounded-xl transition-all focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Últimas Atualizações
            </button>
        </div>
    </div>

    {{-- ============================================================
         1. TAB: MANUAL DO USUÁRIO INTEGRADO
         ============================================================ --}}
    <div x-show="activeTab === 'manual'" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
        
        {{-- Banner Download PDF --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#452463] via-[#35194f] to-[#200c33] p-6 md:p-8 text-white shadow-xl">
            <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-orange-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2 max-w-2xl">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase tracking-wide bg-orange-500 text-white shadow-sm">Edição Oficial</span>
                        <span class="text-xs text-purple-200">Versão 2.0 · Setembro/2026</span>
                    </div>
                    <h2 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight">Manual do Usuário — Amura Sistemas</h2>
                    <p class="text-purple-100/90 text-sm leading-relaxed">
                        Guia consolidado de operação do Painel do Atendente, Painel Administrativo, Base de Conhecimento EasyWiki, Integração Notion, WhatsApp Business e CRM.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ asset('manual/Manual_do_Usuario_AmuraSistemas_v2.pdf') }}" target="_blank"
                       class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-orange-500 hover:bg-orange-600 active:bg-orange-700 text-white font-bold text-sm shadow-lg shadow-orange-500/30 transition-all transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Baixar PDF (v2.0)
                    </a>
                    <a href="{{ asset('manual/Manual_do_Usuario_AmuraSistemas_v2.pdf') }}" target="_blank"
                       class="inline-flex items-center gap-2 px-4 py-3 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-sm backdrop-blur-sm transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        Abrir em Nova Aba
                    </a>
                </div>
            </div>
        </div>

        {{-- Barra de Busca Rápida no Manual --}}
        <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 dark:text-slate-500">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" x-model="manualSearch" placeholder="Filtrar por módulo, funcionalidade, Notion, WhatsApp, RAT..." 
                       class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-slate-100 focus:border-orange-500 focus:ring-2 focus:ring-orange-200 dark:focus:ring-orange-950 transition-all outline-none">
            </div>
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-slate-400">
                <span>Dica: clique nos capítulos abaixo para expandir e ler as orientações.</span>
            </div>
        </div>

        {{-- Capítulos do Manual (Interativo / Accordion) --}}
        <div class="space-y-4">
            
            {{-- Cap 1: Introdução & Perfis --}}
            <div x-show="matchesSearch('introdução perfis público acesso')" class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 overflow-hidden shadow-sm transition-all">
                <button type="button" @click="expandedChapter = (expandedChapter === 1 ? null : 1)" 
                        class="w-full flex items-center justify-between px-6 py-4 text-left bg-gray-50/70 dark:bg-slate-800/60 hover:bg-gray-100/80 dark:hover:bg-slate-700/60 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-950/70 text-orange-700 dark:text-orange-400 font-extrabold text-sm border border-transparent dark:border-orange-800/60">1</span>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-slate-100">Capítulo 1 — Introdução & Perfis de Acesso</h3>
                            <p class="text-xs text-gray-500 dark:text-slate-400">Visão geral do ecossistema, perfis (Atendente, Administrador, CRM) e premissas.</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 dark:text-slate-500 transition-transform duration-200" :class="{ 'rotate-180': expandedChapter === 1 }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="expandedChapter === 1" x-collapse class="p-6 space-y-4 text-sm text-gray-700 dark:text-slate-300 border-t border-gray-100 dark:border-slate-700">
                    <p>O <strong>Sistema de Suporte e Atendimento da Amura Sistemas</strong> centraliza todo o ciclo de vida do relacionamento técnico com o cliente:</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-2">
                        <div class="p-3.5 rounded-xl bg-orange-50/60 dark:bg-orange-950/30 border border-orange-200/50 dark:border-orange-800/50">
                            <span class="text-xs font-bold text-orange-800 dark:text-orange-400 uppercase tracking-wide">Painel do Atendente</span>
                            <p class="text-xs text-orange-950 dark:text-slate-300 mt-1">Abertura e atendimento de tickets, base EasyWiki, agendamentos, geração de RAT e monitor em tempo real.</p>
                        </div>
                        <div class="p-3.5 rounded-xl bg-purple-50/60 dark:bg-purple-950/30 border border-purple-200/50 dark:border-purple-800/50">
                            <span class="text-xs font-bold text-purple-800 dark:text-purple-400 uppercase tracking-wide">Painel Administrativo</span>
                            <p class="text-xs text-purple-950 dark:text-slate-300 mt-1">Gestão de usuários, Dashboard TV para operação, categorias, pesos de SLA, conexões WhatsApp e relatórios gerenciais.</p>
                        </div>
                        <div class="p-3.5 rounded-xl bg-sky-50/60 dark:bg-sky-950/30 border border-sky-200/50 dark:border-sky-800/50">
                            <span class="text-xs font-bold text-sky-800 dark:text-sky-400 uppercase tracking-wide">CRM & Feedback</span>
                            <p class="text-xs text-sky-950 dark:text-slate-300 mt-1">Pesquisas de satisfação, registros de contato comercial, feedbacks e histórico qualitativo por cliente.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cap 2: Requisitos e Login --}}
            <div x-show="matchesSearch('login senha primeiro acesso requisitos navegadores')" class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 overflow-hidden shadow-sm transition-all">
                <button type="button" @click="expandedChapter = (expandedChapter === 2 ? null : 2)" 
                        class="w-full flex items-center justify-between px-6 py-4 text-left bg-gray-50/70 dark:bg-slate-800/60 hover:bg-gray-100/80 dark:hover:bg-slate-700/60 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-950/70 text-orange-700 dark:text-orange-400 font-extrabold text-sm border border-transparent dark:border-orange-800/60">2</span>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-slate-100">Capítulo 2 — Requisitos & Primeiro Acesso</h3>
                            <p class="text-xs text-gray-500 dark:text-slate-400">Navegadores recomendados, troca obrigatória de senha e redefinição segura.</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 dark:text-slate-500 transition-transform duration-200" :class="{ 'rotate-180': expandedChapter === 2 }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="expandedChapter === 2" x-collapse class="p-6 space-y-3 text-sm text-gray-700 dark:text-slate-300 border-t border-gray-100 dark:border-slate-700">
                    <ul class="space-y-2 list-disc list-inside text-gray-600 dark:text-slate-400">
                        <li><strong>Navegadores Homologados:</strong> Google Chrome (120+), Microsoft Edge (120+), Mozilla Firefox (115+) e Safari (16+).</li>
                        <li><strong>Troca Obrigatória de Senha:</strong> No primeiro acesso ou após reset administrativo, o sistema exige a definição de nova senha pessoal com no mínimo 8 caracteres.</li>
                        <li><strong>Recuperação de Senha:</strong> Link com token seguro de expiração automática enviado para o e-mail cadastrado.</li>
                    </ul>
                </div>
            </div>

            {{-- Cap 4: Painel do Atendente (DESTAQUE) --}}
            <div x-show="matchesSearch('atendente chamados tickets easywiki notion whatsapp empresas rat agendamentos monitor tarefas')" class="bg-white dark:bg-slate-800 rounded-2xl border-2 border-orange-200/80 dark:border-orange-500/40 overflow-hidden shadow-md transition-all">
                <button type="button" @click="expandedChapter = (expandedChapter === 4 ? null : 4)" 
                        class="w-full flex items-center justify-between px-6 py-4 text-left bg-gradient-to-r from-orange-50/80 via-white to-orange-50/40 dark:from-orange-950/40 dark:via-slate-800 dark:to-orange-950/20 hover:bg-orange-50 dark:hover:bg-slate-700/60 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-orange-500 text-white font-extrabold text-sm shadow-sm">4</span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-gray-900 dark:text-slate-100">Capítulo 4 — Painel do Atendente & Operação Diária</h3>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 dark:bg-orange-950/70 text-orange-800 dark:text-orange-300 uppercase">Atualizado v2.0</span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-slate-400">Chamados, EasyWiki com Notion, WhatsApp Multimídia, Empresas, Agendamentos e Monitor.</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400 transition-transform duration-200" :class="{ 'rotate-180': expandedChapter === 4 }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="expandedChapter === 4" x-collapse class="p-6 space-y-6 text-sm text-gray-700 dark:text-slate-300 border-t border-orange-100 dark:border-slate-700">
                    
                    {{-- 4.2 Chamados --}}
                    <div class="space-y-2">
                        <h4 class="font-bold text-gray-900 dark:text-slate-100 text-base flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-orange-500"></span> 4.2 Fluxo de Chamados (Tickets)
                        </h4>
                        <p class="text-gray-600 dark:text-slate-400">Estrutura em 5 blocos lógicos: Metadados Iniciais, Contexto Técnico, Detalhamento & Solução, Evidências/Anexos e Finalização com Status/Canal.</p>
                        <div class="bg-gray-50 dark:bg-slate-900/60 p-3.5 rounded-xl border border-gray-200 dark:border-slate-700 text-xs space-y-1.5">
                            <div class="font-semibold text-gray-800 dark:text-slate-200">Principais Recursos:</div>
                            <ul class="list-disc list-inside text-gray-600 dark:text-slate-400 space-y-1">
                                <li><strong>Captura Rápida:</strong> Botão <em>"Puxar para mim"</em> assume o chamado na hora; <em>"Devolver para a fila"</em> libera para redistribuição.</li>
                                <li><strong>Múltiplos Problemas:</strong> Cadastro de múltiplos sub-problemas em um único protocolo oficial.</li>
                                <li><strong>Atualização Rápida:</strong> Edição direta de status e prioridade sem recarregar a tela.</li>
                            </ul>
                        </div>
                    </div>

                    {{-- 4.3 EasyWiki + Notion --}}
                    <div class="space-y-2 p-4 rounded-xl bg-purple-50/50 dark:bg-purple-950/30 border border-purple-200/70 dark:border-purple-800/50">
                        <h4 class="font-bold text-purple-950 dark:text-purple-300 text-base flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-purple-600"></span> 4.3 EasyWiki & Integração Notion (Novidade v2.0)
                        </h4>
                        <p class="text-gray-700 dark:text-slate-300 text-xs leading-relaxed">
                            A base de conhecimento interna agora conta com editor nativo de formatação rica e sincronização completa com o <strong>Notion</strong>:
                        </p>
                        <ul class="text-xs text-purple-900 dark:text-purple-200 space-y-1 list-disc list-inside">
                            <li><strong>Configuração do Notion:</strong> Informar o <em>Token de Integração (Internal Secret)</em> e o <em>ID do Banco de Dados/Página</em> na tela de configurações da EasyWiki.</li>
                            <li><strong>Sincronização Bidirecional:</strong> Importe manuais e procedimentos criados no Notion diretamente para a EasyWiki ou exporte soluções registradas pelos técnicos.</li>
                            <li><strong>Criação a partir de Chamado:</strong> Com 1 clique em um ticket resolvido, transforme o problema e a solução em artigo oficial da EasyWiki.</li>
                        </ul>
                    </div>

                    {{-- 4.2.9 WhatsApp Multimídia --}}
                    <div class="space-y-2 p-4 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/30 border border-emerald-200/70 dark:border-emerald-800/50">
                        <h4 class="font-bold text-emerald-950 dark:text-emerald-300 text-base flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-600"></span> WhatsApp Business & Áudios Integrados
                        </h4>
                        <ul class="text-xs text-emerald-900 dark:text-emerald-200 space-y-1 list-disc list-inside">
                            <li><strong>Gravação com Pré-escuta:</strong> Grave áudios, ouça antes de enviar com o player integrado e descarte gravações com 1 clique se necessário.</li>
                            <li><strong>Liberação Inteligente do Bot:</strong> Ao concluir e fechar o chamado, o bot entra em período de tolerância configurado antes de retomar o atendimento automático para novas mensagens.</li>
                            <li><strong>Respostas Rápidas:</strong> Atalhos como <kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-800 border dark:border-slate-600 rounded text-[11px] font-mono dark:text-slate-200">/sefaz</kbd> aceleram o envio de respostas pré-cadastradas.</li>
                        </ul>
                    </div>

                    {{-- 4.4 Empresas --}}
                    <div class="space-y-2">
                        <h4 class="font-bold text-gray-900 dark:text-slate-100 text-base flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-orange-500"></span> 4.4 Empresas & Integração Financeira
                        </h4>
                        <ul class="text-xs text-gray-600 dark:text-slate-400 space-y-1 list-disc list-inside">
                            <li><strong>Novos Campos:</strong> <em>Código Empresarial</em>, <em>Inscrição Municipal</em> e <em>Telefone 2</em>.</li>
                            <li><strong>Busca Dinâmica Unificada:</strong> Seleção rápida com pesquisa instantânea por CNPJ, Razão Social, Fantasia ou Cidade, com suporte a busca sem acentos.</li>
                            <li><strong>Governança de Status:</strong> A situação cadastral (ativo/inadimplente/inativo) é sincronizada pelo financeiro, garantindo segurança comercial.</li>
                        </ul>
                    </div>

                </div>
            </div>

            {{-- Cap 5: Painel Administrativo & Dashboard TV --}}
            <div x-show="matchesSearch('administrativo dashboard tv usuários sla categorias departamentos relatórios grupos')" class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 overflow-hidden shadow-sm transition-all">
                <button type="button" @click="expandedChapter = (expandedChapter === 5 ? null : 5)" 
                        class="w-full flex items-center justify-between px-6 py-4 text-left bg-gray-50/70 dark:bg-slate-800/60 hover:bg-gray-100/80 dark:hover:bg-slate-700/60 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-950/70 text-orange-700 dark:text-orange-400 font-extrabold text-sm border border-transparent dark:border-orange-800/60">5</span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-gray-900 dark:text-slate-100">Capítulo 5 — Painel Administrativo & Dashboard TV</h3>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 dark:bg-purple-950/70 text-purple-800 dark:text-purple-300 uppercase">Gestão</span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-slate-400">Dashboard TV para telões, SLA dinâmico, grupos empresariais e governança.</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 dark:text-slate-500 transition-transform duration-200" :class="{ 'rotate-180': expandedChapter === 5 }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="expandedChapter === 5" x-collapse class="p-6 space-y-4 text-sm text-gray-700 dark:text-slate-300 border-t border-gray-100 dark:border-slate-700">
                    <div class="p-3.5 rounded-xl bg-slate-900 text-white space-y-2 border border-slate-700">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-orange-400 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                Dashboard TV (Operação Ao Vivo)
                            </span>
                            <span class="text-[11px] text-gray-400">Rota: /admin/dashboard-tv</span>
                        </div>
                        <p class="text-xs text-gray-300">
                            Modo de exibição contínua desenvolvido para televisores e monitores de parede na central de atendimento. Exibe lista de atendentes online em tempo real, volume de chamados aguardando na fila, chamados em atendimento e tempo médio de resposta.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                        <div class="p-3 rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900/60">
                            <strong>Configuração de SLA & Pesos:</strong> Cálculo automático do tempo de criticidade (Atenção, Aviso e Crítico) com base na soma dos pesos das categorias do ticket.
                        </div>
                        <div class="p-3 rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900/60">
                            <strong>Relatórios de Grupos de Clientes:</strong> Agrupamento gerencial para identificar demandas recorrentes por holding ou rede de lojas.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Cap 7: FAQ & Dúvidas --}}
            <div x-show="matchesSearch('faq dúvidas perguntas frequentes problemas')" class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 overflow-hidden shadow-sm transition-all">
                <button type="button" @click="expandedChapter = (expandedChapter === 7 ? null : 7)" 
                        class="w-full flex items-center justify-between px-6 py-4 text-left bg-gray-50/70 dark:bg-slate-800/60 hover:bg-gray-100/80 dark:hover:bg-slate-700/60 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-950/70 text-orange-700 dark:text-orange-400 font-extrabold text-sm border border-transparent dark:border-orange-800/60">7</span>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-slate-100">Capítulo 7 — Perguntas Frequentes (FAQ)</h3>
                            <p class="text-xs text-gray-500 dark:text-slate-400">Resolução rápida para dúvidas operacionais comuns no dia a dia.</p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 dark:text-slate-500 transition-transform duration-200" :class="{ 'rotate-180': expandedChapter === 7 }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="expandedChapter === 7" x-collapse class="p-6 space-y-3 text-sm text-gray-700 dark:text-slate-300 border-t border-gray-100 dark:border-slate-700">
                    <div class="space-y-2">
                        <div class="font-semibold text-gray-900 dark:text-slate-100">P. Como atender múltiplos chamados simultaneamente?</div>
                        <div class="text-xs text-gray-600 dark:text-slate-400 pl-3 border-l-2 border-orange-500">
                            Acesse <em>Minha Conta</em> no canto superior direito e ative a opção <em>"Abrir chamado em nova aba"</em>. Cada atendimento ficará isolado em sua própria guia do navegador.
                        </div>
                    </div>
                    <div class="space-y-2 pt-2">
                        <div class="font-semibold text-gray-900 dark:text-slate-100">P. O que fazer quando a conexão do WhatsApp cair?</div>
                        <div class="text-xs text-gray-600 dark:text-slate-400 pl-3 border-l-2 border-orange-500">
                            O administrador deve acessar <em>Painel Administrativo → WhatsApp Business</em> e realizar a reconexão lendo o QR Code com o aparelho corporativo.
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    {{-- ============================================================
         2. TAB: TECLAS DE ATALHO (HOTKEYS)
         ============================================================ --}}
    <div x-show="activeTab === 'hotkeys'" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;" class="space-y-6">
        
        <div class="bg-indigo-50 dark:bg-indigo-950/40 border-l-4 border-indigo-500 p-4 rounded-r-xl border dark:border-slate-700 dark:border-l-indigo-500">
            <div class="flex items-start">
                <div class="flex-shrink-0 text-indigo-600 dark:text-indigo-400 mt-0.5">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-indigo-900 dark:text-indigo-200 font-medium">Utilize os atalhos de teclado para navegar e processar tickets com máxima velocidade sem tirar as mãos do teclado.</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            
            {{-- Navigation Hotkeys --}}
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-base font-bold text-gray-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                    <span class="p-1.5 bg-sky-100 dark:bg-sky-950/70 text-sky-600 dark:text-sky-400 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg></span>
                    Navegação Rápida
                </h3>
                <ul class="space-y-3.5">
                    <li class="flex items-center gap-3 text-sm text-gray-600 dark:text-slate-300">
                        <kbd class="hotkey">1</kbd>
                        <span>Abre a aba dos chamados <strong>Pendentes</strong></span>
                    </li>
                    <li class="flex items-center gap-3 text-sm text-gray-600 dark:text-slate-300">
                        <kbd class="hotkey">2</kbd>
                        <span>Abre a aba dos <strong>Meus Chamados</strong></span>
                    </li>
                    <li class="flex items-center gap-3 text-sm text-gray-600 dark:text-slate-300">
                        <div class="flex gap-1">
                            <kbd class="hotkey">Ctrl</kbd> <span class="text-gray-400">+</span> <kbd class="hotkey">A</kbd>
                        </div>
                        <span>Abre a tela de <strong>Novo Chamado</strong></span>
                    </li>
                </ul>
            </div>

            {{-- Form Hotkeys --}}
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-base font-bold text-gray-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                    <span class="p-1.5 bg-emerald-100 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></span>
                    Ações de Salvamento
                </h3>
                <ul class="space-y-3.5">
                    <li class="flex items-center gap-3 text-sm text-gray-600 dark:text-slate-300">
                        <div class="flex gap-1">
                            <kbd class="hotkey">Ctrl</kbd> <span class="text-gray-400">+</span> <kbd class="hotkey">S</kbd>
                        </div>
                        <span>Aciona o botão <strong>Salvar Protocolo</strong></span>
                    </li>
                    <li class="flex items-center gap-3 text-sm text-gray-600 dark:text-slate-300">
                        <div class="flex gap-1">
                            <kbd class="hotkey">Ctrl</kbd> <span class="text-gray-400">+</span> <kbd class="hotkey">F</kbd>
                        </div>
                        <span><strong>Salva e Fecha</strong> o formulário</span>
                    </li>
                    <li class="flex items-center gap-3 text-sm text-gray-600 dark:text-slate-300">
                        <div class="flex gap-1">
                            <kbd class="hotkey">Ctrl</kbd> <span class="text-gray-400">+</span> <kbd class="hotkey">E</kbd>
                        </div>
                        <span>Altera status para <strong>Resolvido</strong> e salva</span>
                    </li>
                </ul>
            </div>
            
            {{-- Window Control --}}
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow">
                <h3 class="text-base font-bold text-gray-800 dark:text-slate-100 mb-4 flex items-center gap-2">
                    <span class="p-1.5 bg-rose-100 dark:bg-rose-950/70 text-rose-600 dark:text-rose-400 rounded-lg"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></span>
                    Controle de Abas
                </h3>
                <ul class="space-y-3.5">
                    <li class="flex items-center gap-3 text-sm text-gray-600 dark:text-slate-300">
                        <div class="flex gap-1">
                            <kbd class="hotkey">Ctrl</kbd> <span class="text-gray-400">+</span> <kbd class="hotkey">W</kbd>
                        </div>
                        <span>Fecha a janela/aba ativa no momento</span>
                    </li>
                    <li class="flex items-center gap-3 text-sm text-gray-600 dark:text-slate-300">
                        <div class="flex gap-1">
                            <kbd class="hotkey">Esc</kbd>
                        </div>
                        <span>Fecha modais e janelas secundárias abertas</span>
                    </li>
                </ul>
            </div>

        </div>
    </div>

    {{-- ============================================================
         3. TAB: ÚLTIMAS ATUALIZAÇÕES (HISTÓRICO COMPLETO DO PROJETO)
         ============================================================ --}}
    <div x-show="activeTab === 'changelog'" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;" class="space-y-6">
        
        <div class="relative max-w-4xl mx-auto">
            {{-- Central Timeline Line --}}
            <div class="absolute left-4 md:left-8 top-0 bottom-0 w-0.5 bg-gradient-to-b from-orange-500 via-purple-500 to-gray-200 dark:to-slate-700"></div>

            <div class="space-y-8 relative">
                
                {{-- Versão 2.0 (Setembro 2026) --}}
                <div class="relative pl-12 md:pl-24 group">
                    <div class="absolute left-2.5 md:left-[1.8rem] top-2 w-4 h-4 bg-orange-500 border-4 border-orange-100 dark:border-orange-950 rounded-full shadow"></div>
                    <div class="mb-1 md:absolute md:left-0 md:top-2 md:-ml-24 md:w-20 md:text-right">
                        <span class="inline-block text-xs font-black text-orange-600 dark:text-orange-400 uppercase tracking-wider">v2.0</span>
                        <time class="block text-[11px] font-bold text-gray-500 dark:text-slate-400">01/09/2026</time>
                    </div>
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border-2 border-orange-200 dark:border-orange-500/40 shadow-sm space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 dark:border-slate-700 pb-3">
                            <h4 class="text-base font-extrabold text-gray-900 dark:text-slate-100">Integração Notion, WhatsApp Multimídia & Dashboard TV</h4>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-100 dark:bg-orange-950/70 text-orange-800 dark:text-orange-300">Versão Principal</span>
                        </div>
                        <ul class="text-sm text-gray-600 dark:text-slate-300 space-y-2">
                            <li class="flex items-start gap-2">
                                <span class="badge-feature">Notion</span>
                                <span><strong>Integração Notion na EasyWiki:</strong> Configuração visual de Token e ID de Banco de Dados com sincronização de procedimentos e manuais técnicos.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="badge-feature">EasyWiki</span>
                                <span><strong>Novo Editor com Barra Nativa:</strong> Formatação rica e fluxo aprimorado de edição de artigos (<code class="text-xs dark:text-orange-300">edit.blade.php</code>).</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="badge-feature">WhatsApp</span>
                                <span><strong>Multimídia & Bot Inteligente:</strong> Reprodução/envio de áudios nativos e delay automático de liberação do bot após fechar tickets.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="badge-feature">Admin</span>
                                <span><strong>Dashboard TV:</strong> Painel em tela cheia para monitoramento contínuo da operação em TVs com status de atendentes online.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="badge-feature">Empresas</span>
                                <span><strong>Novos Campos & Busca Dinâmica:</strong> Código Empresarial, Inscrição Municipal, Telefone 2 e seletor com busca sem acentos.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Versão 1.9 (Agosto 2026) --}}
                <div class="relative pl-12 md:pl-24 group">
                    <div class="absolute left-2.5 md:left-[1.8rem] top-2 w-3.5 h-3.5 bg-purple-500 border-2 border-purple-100 dark:border-purple-950 rounded-full"></div>
                    <div class="mb-1 md:absolute md:left-0 md:top-2 md:-ml-24 md:w-20 md:text-right">
                        <span class="inline-block text-xs font-black text-purple-600 dark:text-purple-400 uppercase tracking-wider">v1.9</span>
                        <time class="block text-[11px] font-bold text-gray-500 dark:text-slate-400">20/08/2026</time>
                    </div>
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-sm space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 dark:border-slate-700 pb-3">
                            <h4 class="text-base font-bold text-gray-900 dark:text-slate-100">Roteamento Departamental & Proteção de Dados</h4>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 dark:bg-purple-950/70 text-purple-800 dark:text-purple-300">Melhoria</span>
                        </div>
                        <ul class="text-sm text-gray-600 dark:text-slate-300 space-y-2">
                            <li class="flex items-start gap-2">
                                <span class="badge-feature">Roteamento</span>
                                <span>Vinculação estrita de Categorias aos Departamentos com notificações automáticas de transferência.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="badge-feature">Auditoria</span>
                                <span>Timeline de modificações com destaque para trocas de departamento, responsável e histórico de status.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="badge-feature">Relatórios</span>
                                <span>Novo relatório consolidado de chamados segmentado por departamento e tempo médio de solução.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Versão 1.8 (Julho 2026) --}}
                <div class="relative pl-12 md:pl-24 group">
                    <div class="absolute left-2.5 md:left-[1.8rem] top-2 w-3.5 h-3.5 bg-blue-500 border-2 border-blue-100 dark:border-blue-950 rounded-full"></div>
                    <div class="mb-1 md:absolute md:left-0 md:top-2 md:-ml-24 md:w-20 md:text-right">
                        <span class="inline-block text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-wider">v1.8</span>
                        <time class="block text-[11px] font-bold text-gray-500 dark:text-slate-400">15/07/2026</time>
                    </div>
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-sm space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 dark:border-slate-700 pb-3">
                            <h4 class="text-base font-bold text-gray-900 dark:text-slate-100">Sincronização com Sistema Financeiro & Grupos de Clientes</h4>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-950/70 text-blue-800 dark:text-blue-300">Integração</span>
                        </div>
                        <ul class="text-sm text-gray-600 dark:text-slate-300 space-y-2">
                            <li class="flex items-start gap-2">
                                <span class="badge-feature">Financeiro</span>
                                <span>API de sincronização em tempo real de clientes, contatos comerciais e bloqueio automático de inadimplência.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="badge-feature">Grupos</span>
                                <span>Criação automática e organização por Grupos Empresariais com histórico unificado de atendimento.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Versão 1.5 (Junho 2026) --}}
                <div class="relative pl-12 md:pl-24 group">
                    <div class="absolute left-2.5 md:left-[1.8rem] top-2 w-3.5 h-3.5 bg-teal-500 border-2 border-teal-100 dark:border-teal-950 rounded-full"></div>
                    <div class="mb-1 md:absolute md:left-0 md:top-2 md:-ml-24 md:w-20 md:text-right">
                        <span class="inline-block text-xs font-black text-teal-600 dark:text-teal-400 uppercase tracking-wider">v1.5</span>
                        <time class="block text-[11px] font-bold text-gray-500 dark:text-slate-400">10/06/2026</time>
                    </div>
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-sm space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 dark:border-slate-700 pb-3">
                            <h4 class="text-base font-bold text-gray-900 dark:text-slate-100">Módulo de Implantação, Agendamentos & RAT</h4>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-teal-100 dark:bg-teal-950/70 text-teal-800 dark:text-teal-300">Módulo</span>
                        </div>
                        <ul class="text-sm text-gray-600 dark:text-slate-300 space-y-2">
                            <li class="flex items-start gap-2">
                                <span class="badge-feature">RAT</span>
                                <span>Geração de Relatórios de Atendimento com checklists dinâmicos parametrizados por produto contratado.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="badge-feature">Agenda</span>
                                <span>Calendário condensado com timeline semanal integrada de chamados, visitas técnicas e consultorias.</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Versão 1.0 (Maio 2026) --}}
                <div class="relative pl-12 md:pl-24 group">
                    <div class="absolute left-2.5 md:left-[1.8rem] top-2 w-3.5 h-3.5 bg-gray-400 border-2 border-gray-200 dark:border-slate-600 rounded-full"></div>
                    <div class="mb-1 md:absolute md:left-0 md:top-2 md:-ml-24 md:w-20 md:text-right">
                        <span class="inline-block text-xs font-black text-gray-700 dark:text-slate-400 uppercase tracking-wider">v1.0</span>
                        <time class="block text-[11px] font-bold text-gray-500 dark:text-slate-500">02/05/2026</time>
                    </div>
                    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-sm space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 dark:border-slate-700 pb-3">
                            <h4 class="text-base font-bold text-gray-900 dark:text-slate-100">Lançamento do Sistema de Suporte e Atendimento Amura</h4>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-slate-200">Marco Inicial</span>
                        </div>
                        <ul class="text-sm text-gray-600 dark:text-slate-300 space-y-2">
                            <li class="flex items-start gap-2">
                                <span class="badge-feature">Core</span>
                                <span>Lançamento da plataforma web unificada (Painel do Atendente, Painel Administrativo, Helpdesk com SLA, Monitor de Fila e CRM).</span>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<style>
    /* Styling for keyboard keys mimicking real hardware keys */
    .hotkey {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.6rem;
        height: 1.6rem;
        padding: 0 0.45rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.85rem;
        font-weight: 700;
        line-height: 1;
        color: #1e293b;
        background-color: #f8fafc;
        border: 1px solid #cbd5e1;
        border-bottom-width: 2.5px;
        border-radius: 0.4rem;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }

    html.ocean .hotkey {
        color: #f1f5f9 !important;
        background-color: #1e293b !important;
        border-color: #475569 !important;
        border-bottom-color: #64748b !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.4) !important;
    }
    
    .badge-feature {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.15rem 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background-color: #fff3e6;
        color: #f26522;
        border: 1px solid rgba(242, 101, 34, 0.25);
        flex-shrink: 0;
        margin-top: 0.1rem;
    }

    html.ocean .badge-feature {
        background-color: rgba(234, 88, 12, 0.18) !important;
        color: #fb923c !important;
        border-color: rgba(234, 88, 12, 0.4) !important;
    }

    /* Structural animations */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up {
        animation: fadeInUp 0.35s ease-out forwards;
    }
</style>
@endsection
