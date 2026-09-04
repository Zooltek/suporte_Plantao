#!/bin/bash
# ==============================================================================
# WhatsApp Chatbot - Script de Diagnóstico e Correção
# ==============================================================================
# Uso: bash scripts/whatsapp-diagnostico.sh
#
# Este script verifica todos os componentes necessários para o chatbot WhatsApp
# funcionar corretamente e tenta corrigir problemas encontrados.
# ==============================================================================

set -e

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

OK="${GREEN}[OK]${NC}"
FAIL="${RED}[FALHA]${NC}"
WARN="${YELLOW}[AVISO]${NC}"
INFO="${BLUE}[INFO]${NC}"

ERRORS=0

echo ""
echo "=============================================="
echo "  WhatsApp Chatbot - Diagnóstico"
echo "=============================================="
echo ""

# --------------------------------------------------------------------------
# 1. Containers rodando
# --------------------------------------------------------------------------
echo -e "${INFO} 1/6 — Verificando containers..."

for CONTAINER in suporte12_app suporte12_redis suporte12_worker suporte12_evolution; do
    STATUS=$(docker ps --filter "name=${CONTAINER}" --format "{{.Status}}" 2>/dev/null)
    if [ -z "$STATUS" ]; then
        echo -e "  ${FAIL} ${CONTAINER} não está rodando"
        ERRORS=$((ERRORS + 1))
    else
        echo -e "  ${OK} ${CONTAINER} → ${STATUS}"
    fi
done

echo ""

# --------------------------------------------------------------------------
# 2. Redis acessível
# --------------------------------------------------------------------------
echo -e "${INFO} 2/6 — Verificando Redis..."

REDIS_PING=$(docker exec suporte12_app php -r "
try {
    \$r = new Redis();
    \$r->connect('suporte12_redis', 6379);
    echo 'PONG';
} catch (Exception \$e) {
    echo 'FAIL:' . \$e->getMessage();
}
" 2>&1)

if [ "$REDIS_PING" = "PONG" ]; then
    echo -e "  ${OK} Redis respondendo"
else
    echo -e "  ${FAIL} Redis inacessível: ${REDIS_PING}"
    ERRORS=$((ERRORS + 1))
fi

echo ""

# --------------------------------------------------------------------------
# 3. Queue Worker processando jobs
# --------------------------------------------------------------------------
echo -e "${INFO} 3/6 — Verificando Queue Worker..."

WORKER_PID=$(docker exec suporte12_worker pgrep -f "artisan queue:work" 2>/dev/null || echo "")

if [ -n "$WORKER_PID" ]; then
    echo -e "  ${OK} Queue worker rodando (PID: ${WORKER_PID})"
else
    echo -e "  ${FAIL} Queue worker NÃO está rodando"
    echo -e "  ${WARN} Tentando iniciar..."
    docker restart suporte12_worker 2>/dev/null
    sleep 3
    WORKER_PID=$(docker exec suporte12_worker pgrep -f "artisan queue:work" 2>/dev/null || echo "")
    if [ -n "$WORKER_PID" ]; then
        echo -e "  ${OK} Queue worker reiniciado com sucesso"
    else
        echo -e "  ${FAIL} Não foi possível iniciar o queue worker"
        ERRORS=$((ERRORS + 1))
    fi
fi

echo ""

# --------------------------------------------------------------------------
# 4. Evolution API acessível
# --------------------------------------------------------------------------
echo -e "${INFO} 4/6 — Verificando Evolution API..."

# Ler API key do .env
API_KEY=$(grep "^WHATSAPP_EVOLUTION_API_KEY=" .env 2>/dev/null | cut -d'=' -f2)
INSTANCE=$(grep "^WHATSAPP_EVOLUTION_INSTANCE=" .env 2>/dev/null | cut -d'=' -f2)
API_URL=$(grep "^WHATSAPP_API_URL=" .env 2>/dev/null | cut -d'=' -f2)
WEBHOOK_TARGET_URL=$(grep "^WHATSAPP_WEBHOOK_URL=" .env 2>/dev/null | cut -d'=' -f2- | tr -d '"')
if [ -z "$WEBHOOK_TARGET_URL" ]; then
    WEBHOOK_TARGET_URL="http://suporte12_app:8080/api/webhook/whatsapp"
fi

if [ -z "$API_KEY" ] || [ -z "$INSTANCE" ] || [ -z "$API_URL" ]; then
    echo -e "  ${FAIL} Variáveis WhatsApp não encontradas no .env"
    echo -e "         Verifique: WHATSAPP_EVOLUTION_API_KEY, WHATSAPP_EVOLUTION_INSTANCE, WHATSAPP_API_URL"
    ERRORS=$((ERRORS + 1))
else
    echo -e "  ${OK} Configuração: instância=${INSTANCE}, url=${API_URL}"

    EVO_RESP=$(docker exec suporte12_app curl -s -m 10 "${API_URL}" 2>&1 || echo "TIMEOUT")
    if echo "$EVO_RESP" | grep -q "Evolution API"; then
        EVO_VERSION=$(echo "$EVO_RESP" | python3 -c "import sys,json; print(json.load(sys.stdin).get('version','?'))" 2>/dev/null || echo "?")
        echo -e "  ${OK} Evolution API acessível (v${EVO_VERSION})"
    else
        echo -e "  ${FAIL} Evolution API inacessível: ${EVO_RESP}"
        ERRORS=$((ERRORS + 1))
    fi
fi

echo ""

# --------------------------------------------------------------------------
# 5. Instância conectada ao WhatsApp
# --------------------------------------------------------------------------
echo -e "${INFO} 5/6 — Verificando conexão da instância..."

if [ -n "$API_KEY" ] && [ -n "$INSTANCE" ] && [ -n "$API_URL" ]; then
    STATE_RESP=$(docker exec suporte12_app curl -s -m 15 "${API_URL}/instance/connectionState/${INSTANCE}" \
        -H "apikey: ${API_KEY}" 2>&1 || echo "TIMEOUT")

    STATE=$(echo "$STATE_RESP" | python3 -c "import sys,json; print(json.load(sys.stdin)['instance']['state'])" 2>/dev/null || echo "unknown")

    if [ "$STATE" = "open" ]; then
        echo -e "  ${OK} WhatsApp conectado (state: open)"
    elif [ "$STATE" = "connecting" ]; then
        echo -e "  ${WARN} WhatsApp está tentando conectar (state: connecting)"
        echo -e "         Acesse o painel admin para escanear o QR Code"
        ERRORS=$((ERRORS + 1))
    elif [ "$STATE" = "close" ]; then
        echo -e "  ${FAIL} WhatsApp DESCONECTADO (state: close)"
        echo -e ""
        echo -e "  ┌──────────────────────────────────────────────────┐"
        echo -e "  │  AÇÃO NECESSÁRIA: Escanear QR Code               │"
        echo -e "  │                                                   │"
        echo -e "  │  Opção 1: Painel Admin                           │"
        echo -e "  │    Acesse: http://localhost:8090/admin/whatsapp   │"
        echo -e "  │    Clique em 'Gerar QR Code' e escaneie          │"
        echo -e "  │                                                   │"
        echo -e "  │  Opção 2: Manager da Evolution                   │"
        echo -e "  │    Acesse: http://localhost:8091/manager          │"
        echo -e "  │    Clique na instância e escaneie o QR Code      │"
        echo -e "  └──────────────────────────────────────────────────┘"
        ERRORS=$((ERRORS + 1))
    else
        echo -e "  ${FAIL} Estado desconhecido: ${STATE_RESP}"
        ERRORS=$((ERRORS + 1))
    fi
else
    echo -e "  ${WARN} Pulando (configuração incompleta)"
fi

echo ""

# --------------------------------------------------------------------------
# 6. Webhook configurado
# --------------------------------------------------------------------------
echo -e "${INFO} 6/6 — Verificando webhook..."

if [ -n "$API_KEY" ] && [ -n "$INSTANCE" ] && [ -n "$API_URL" ]; then
    WEBHOOK_RESP=$(docker exec suporte12_app curl -s -m 15 "${API_URL}/webhook/find/${INSTANCE}" \
        -H "apikey: ${API_KEY}" 2>&1 || echo "TIMEOUT")

    WEBHOOK_URL=$(echo "$WEBHOOK_RESP" | python3 -c "import sys,json; print(json.load(sys.stdin).get('url',''))" 2>/dev/null || echo "")
    WEBHOOK_ENABLED=$(echo "$WEBHOOK_RESP" | python3 -c "import sys,json; print(json.load(sys.stdin).get('enabled',''))" 2>/dev/null || echo "")
    WEBHOOK_EVENTS_OK=$(echo "$WEBHOOK_RESP" | python3 -c "import sys,json; data=json.load(sys.stdin); events=data.get('events') or []; print('true' if all(e in events for e in ['MESSAGES_UPSERT','MESSAGES_SET']) else 'false')" 2>/dev/null || echo "false")

    if [ "$WEBHOOK_URL" = "$WEBHOOK_TARGET_URL" ] && [ "$WEBHOOK_ENABLED" = "True" ] && [ "$WEBHOOK_EVENTS_OK" = "true" ]; then
        echo -e "  ${OK} Webhook ativo: ${WEBHOOK_URL}"
    else
        echo -e "  ${FAIL} Webhook não configurado, desabilitado ou divergente"
        echo -e "         Configurando webhook automaticamente..."

        docker exec suporte12_app curl -s -m 15 -X POST "${API_URL}/webhook/set/${INSTANCE}" \
            -H "apikey: ${API_KEY}" \
            -H "Content-Type: application/json" \
            -d "{
                \"webhook\": {
                    \"enabled\": true,
                    \"url\": \"${WEBHOOK_TARGET_URL}\",
                    \"headers\": {\"apikey\": \"${API_KEY}\"},
                    \"byEvents\": false,
                    \"base64\": false,
                    \"events\": [\"MESSAGES_UPSERT\", \"MESSAGES_SET\"]
                }
            }" > /dev/null 2>&1

        echo -e "  ${OK} Webhook configurado"
    fi
else
    echo -e "  ${WARN} Pulando (configuração incompleta)"
fi

echo ""

# --------------------------------------------------------------------------
# Resultado
# --------------------------------------------------------------------------
echo "=============================================="
if [ $ERRORS -eq 0 ]; then
    echo -e "  ${GREEN}Tudo OK! O chatbot está pronto para uso.${NC}"
    echo ""
    echo "  Envie uma mensagem de outro número para o"
    echo "  WhatsApp conectado e o chatbot irá responder."
else
    echo -e "  ${RED}${ERRORS} problema(s) encontrado(s).${NC}"
    echo ""
    echo "  Corrija os problemas acima e execute novamente:"
    echo "  bash scripts/whatsapp-diagnostico.sh"
fi
echo "=============================================="
echo ""
