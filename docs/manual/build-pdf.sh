#!/usr/bin/env bash
#
# Gera o Manual do Usuário em PDF a partir dos arquivos HTML.
#
# Pipeline:
#   cover.html  --(WeasyPrint)-->  cover.pdf   (1 página, capa)
#   manual.html --(WeasyPrint)-->  manual.pdf  (conteúdo, com header/rodapé e "Página X de Y")
#   cover.pdf + manual.pdf --(pdfunite)--> Manual_do_Usuario_AmuraSistemas_v1.pdf
#
# Dependências:
#   - WeasyPrint (motor de paged-media; renderiza @page, margin-boxes e counter(page)).
#       Instale num venv:  python3 -m venv .venv && .venv/bin/pip install weasyprint
#       Aponte a variável WEASYPRINT para o binário, ou deixe-o no PATH.
#   - pdfunite (pacote poppler-utils) para mesclar a capa ao conteúdo.
#
# As capturas de tela ficam em assets/screens/ e são referenciadas no manual.html.
# Regerá-las:  veja o procedimento no relatório (Playwright + Chrome, painel do atendente).
#
set -euo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OUT="${DIR}/Manual_do_Usuario_AmuraSistemas_v1.pdf"
WEASYPRINT="${WEASYPRINT:-weasyprint}"

command -v "$WEASYPRINT" >/dev/null 2>&1 || { echo "ERRO: WeasyPrint não encontrado (defina WEASYPRINT=/caminho/weasyprint)"; exit 1; }
command -v pdfunite     >/dev/null 2>&1 || { echo "ERRO: pdfunite não encontrado (instale poppler-utils)"; exit 1; }

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

echo "→ Renderizando capa..."
"$WEASYPRINT" "${DIR}/cover.html"  "${TMP}/cover.pdf"

echo "→ Renderizando conteúdo..."
"$WEASYPRINT" "${DIR}/manual.html" "${TMP}/manual.pdf"

echo "→ Mesclando capa + conteúdo..."
pdfunite "${TMP}/cover.pdf" "${TMP}/manual.pdf" "$OUT"

echo "✓ Gerado: $OUT ($(pdfinfo "$OUT" 2>/dev/null | awk '/^Pages/{print $2" páginas"}'))"
