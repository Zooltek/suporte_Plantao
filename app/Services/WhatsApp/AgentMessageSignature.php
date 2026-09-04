<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Assina mensagens externas enviadas por um atendente humano com o nome do
 * agente, para que o cliente saiba com quem está falando no WhatsApp.
 *
 * A assinatura é puramente de apresentação: nunca altera o corpo persistido
 * no histórico (o painel já atribui o autor pela coluna user_id) — apenas o
 * texto entregue ao provedor. Mensagens do chatbot e notas internas não são
 * assinadas.
 *
 * O formato é configurável em config/whatsapp.php (agent_signature.template),
 * suportando os placeholders {name}, {first_name} e {message}.
 */
class AgentMessageSignature
{
    /**
     * Prefixa o corpo com o nome do agente conforme o template configurado.
     * Retorna o corpo intacto quando a assinatura está desligada ou o agente
     * não possui nome utilizável.
     */
    public function apply(string $message, User $agent): string
    {
        if (! config('whatsapp.agent_signature.enabled', false)) {
            return $message;
        }

        $name = trim((string) $agent->name);

        if ($name === '') {
            return $message;
        }

        $template = (string) config('whatsapp.agent_signature.template', "*{name}*\n{message}");

        $rendered = strtr($template, [
            '{name}' => $name,
            '{first_name}' => $this->firstName($name),
            '{message}' => $message,
        ]);

        // rtrim cobre o caso de mídia sem legenda ({message} vazio), evitando
        // enviar uma quebra de linha solta após o nome.
        return rtrim($rendered);
    }

    /**
     * Versão tolerante a legenda nula (mídia): retorna null quando o resultado
     * ficaria vazio, preservando o comportamento de "enviar sem legenda".
     */
    public function applyToCaption(?string $caption, User $agent): ?string
    {
        $signed = $this->apply($caption ?? '', $agent);

        return $signed === '' ? null : $signed;
    }

    private function firstName(string $name): string
    {
        return (string) Str::of($name)->squish()->explode(' ')->first();
    }
}
