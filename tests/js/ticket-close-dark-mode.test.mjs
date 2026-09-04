import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(import.meta.dirname, '..', '..');
const ticketShowView = readFileSync(
    path.join(repoRoot, 'resources', 'views', 'agent', 'ticket', 'show.blade.php'),
    'utf8',
);
const appCss = readFileSync(
    path.join(repoRoot, 'resources', 'css', 'app.css'),
    'utf8',
);

describe('Ticket close panel dark mode', () => {
    it('mantem contraste explicito para status e botao cancelar no tema ocean', () => {
        assert.match(ticketShowView, /dark:bg-slate-900/);
        assert.match(ticketShowView, /dark:text-amber-100/);
        assert.match(ticketShowView, /dark:bg-slate-800\/80/);
        assert.match(ticketShowView, /dark:text-slate-100/);
        assert.match(ticketShowView, /dark:text-slate-300/);
        assert.match(ticketShowView, /dark:bg-slate-700/);
        assert.match(ticketShowView, /dark:hover:bg-slate-600/);
    });

    it('evita o par bg-gray-100 text-gray-700 no cancelar do fechamento', () => {
        const closePanelMatch = ticketShowView.match(
            /Painel de confirmação: Fechar Chamado[\s\S]*?Painel de confirmação: Excluir Chamado/,
        );

        assert.ok(closePanelMatch, 'painel de fechamento nao encontrado');
        assert.doesNotMatch(closePanelMatch[0], /bg-gray-100[\s\S]*text-gray-700/);
    });

    it('protege botoes da regra de texto escuro de badges bg-slate-100 no tema ocean', () => {
        // A proteção de badges força `color: #334155 !important` sobre qualquer
        // elemento com bg-slate-100 + text-slate-{500,600,700}. O botão Cancelar
        // do fechamento usa exatamente esse par (mais dark:bg-slate-700), então o
        // !important anulava o dark:text-slate-100 e o texto ficava da mesma cor
        // do fundo escuro — invisível. Botões precisam ficar de fora da regra.
        const slateBadgeSelector = appCss.match(
            /html\.ocean\s+\.bg-slate-100\.text-slate-700[^,{]*/,
        );

        assert.ok(slateBadgeSelector, 'regra de protecao bg-slate-100/text-slate-700 nao encontrada');
        assert.match(slateBadgeSelector[0], /:not\(button\)/);
    });
});
