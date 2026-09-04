#!/usr/bin/env bash
# =============================================================================
# test-chatbot.sh — Simula o fluxo completo do chatbot WhatsApp localmente
#
# Uso:
#   bash scripts/test-chatbot.sh
#
# Não precisa de celular nem QR Code.
# Envia mensagens direto no webhook (formato Evolution API).
# Requer: containers suporte12_app e suporte12_worker rodando.
# =============================================================================

WEBHOOK="http://localhost:8090/api/webhook/whatsapp"
PHONE="5527$(( RANDOM % 90000000 + 10000000 ))"   # número único por execução
INSTANCE="amura-local"
DELAY=4   # segundos entre mensagens (aguarda o worker Redis processar)

C_BOLD='\033[1m'
C_GREEN='\033[0;32m'
C_CYAN='\033[0;36m'
C_YELLOW='\033[1;33m'
C_RED='\033[0;31m'
C_RESET='\033[0m'

MSG_SEQ=0

# ---------------------------------------------------------------------------
send() {
    local BODY="$1"
    local LABEL="${2:-$1}"
    MSG_SEQ=$((MSG_SEQ + 1))

    local PAYLOAD
    PAYLOAD=$(printf '{
        "event":"messages.upsert",
        "instance":"%s",
        "data":{
            "key":{"remoteJid":"%s@s.whatsapp.net","fromMe":false,"id":"T%s-%d"},
            "message":{"conversation":"%s"},
            "messageType":"conversation",
            "messageTimestamp":%d
        }
    }' "$INSTANCE" "$PHONE" "$(date +%s)" "$MSG_SEQ" "$BODY" "$(date +%s)")

    echo -e "${C_CYAN}[→ USER]${C_RESET} ${C_BOLD}${LABEL}${C_RESET}"

    RESP=$(curl -s -X POST \
        -H "Content-Type: application/json" \
        -d "$PAYLOAD" "$WEBHOOK" 2>&1)

    if echo "$RESP" | grep -q '"queued"'; then
        echo -e "         ${C_GREEN}✓ queued${C_RESET}"
    else
        echo -e "         ${C_RED}⚠ $RESP${C_RESET}"
    fi

    sleep "$DELAY"
}

state() {
    docker exec suporte12_app php -r "
        require 'vendor/autoload.php';
        \$app = require 'bootstrap/app.php';
        \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        \$c = App\Models\WhatsApp\WhatsAppConversation::where('phone','${PHONE}')->latest()->first();
        echo \$c ? \$c->state->value : '(aguardando)';
    " 2>/dev/null | grep -v "Deprecat"
}

# ---------------------------------------------------------------------------
echo ""
echo -e "${C_BOLD}================================================================${C_RESET}"
echo -e "${C_BOLD} TESTE CHATBOT WHATSAPP — Número: +${PHONE}${C_RESET}"
echo -e "${C_BOLD}================================================================${C_RESET}"
echo ""

send "oi"
echo -e "  ${C_YELLOW}Estado: $(state)${C_RESET}"
echo ""

send "1" "1 → Abrir chamado"
echo -e "  ${C_YELLOW}Estado: $(state)${C_RESET}"
echo ""

send "João Silva" "João Silva (nome)"
echo -e "  ${C_YELLOW}Estado: $(state)${C_RESET}"
echo ""

send "Empresa Teste" "Empresa Teste (empresa)"
echo -e "  ${C_YELLOW}Estado: $(state)${C_RESET}"
echo ""

send "1" "1 → Suporte Técnico (área)"
echo -e "  ${C_YELLOW}Estado: $(state)${C_RESET}"
echo ""

send "Sistema trava ao abrir relatorios" "descrevendo problema..."
echo -e "  ${C_YELLOW}Estado: $(state)${C_RESET}"
echo ""

send "confirmar" "confirmar → sem anexos"
echo -e "  ${C_YELLOW}Estado: $(state)${C_RESET}"
echo ""

send "sim" "sim → criar ticket"
echo -e "  ${C_YELLOW}Estado: $(state)${C_RESET}"
echo ""

# Resultado final
echo -e "${C_BOLD}================================================================${C_RESET}"
echo -e "${C_BOLD} RESULTADO FINAL${C_RESET}"
echo -e "${C_BOLD}================================================================${C_RESET}"

docker exec suporte12_app php -r "
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    \$c = App\Models\WhatsApp\WhatsAppConversation::where('phone','${PHONE}')->latest()->first();
    if (!\$c) { echo 'Conversa nao encontrada.' . PHP_EOL; exit; }

    echo 'Estado final : ' . \$c->state->value . PHP_EOL;
    echo 'Ticket ID    : ' . (\$c->ticket_id ?? 'nao criado') . PHP_EOL;
    echo 'Concluida em : ' . (\$c->completed_at ?? '-') . PHP_EOL;

    if (\$c->ticket_id) {
        \$t = App\Models\Ticket\Ticket::find(\$c->ticket_id);
        if (\$t) {
            echo '--- Ticket criado ---' . PHP_EOL;
            echo 'Assunto  : ' . \$t->subject . PHP_EOL;
            echo 'Origin ID: ' . \$t->origin_id . PHP_EOL;
            echo 'Status ID: ' . \$t->status_id . PHP_EOL;
        }
    }

    echo PHP_EOL . '--- Historico de mensagens ---' . PHP_EOL;
    \$msgs = App\Models\WhatsApp\WhatsAppMessage::where('conversation_id',\$c->id)->orderBy('id')->get();
    foreach(\$msgs as \$m) {
        \$dir = \$m->direction === 'inbound' ? 'USER→ ' : '←BOT  ';
        echo \$dir . substr(\$m->body,0,90) . PHP_EOL;
    }
" 2>/dev/null | grep -v "Deprecat"

echo ""
echo -e "${C_GREEN}${C_BOLD}Painel admin: http://localhost:8090/support/whatsapp${C_RESET}"
echo ""
