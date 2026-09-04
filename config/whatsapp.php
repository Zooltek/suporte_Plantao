<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business API
    |--------------------------------------------------------------------------
    |
    | Configurações do provedor parceiro de WhatsApp (Twilio, Zenvia, Gupshup
    | ou qualquer provedor compatível com o contrato WhatsAppProviderContract).
    |
    | Número oficial: 27981180125
    |
    */

    'enabled' => env('WHATSAPP_ENABLED', false),

    'provider' => env('WHATSAPP_PROVIDER', 'generic'), // generic | evolution

    'from_number' => env('WHATSAPP_FROM_NUMBER', '27981180125'),

    /*
    |--------------------------------------------------------------------------
    | Assinatura do atendente
    |--------------------------------------------------------------------------
    |
    | Quando habilitada, as mensagens enviadas pelo atendente humano ao cliente
    | (texto, legendas de mídia e edições) são prefixadas com o nome do agente,
    | para que o cliente saiba com quem está falando. Não afeta o chatbot nem as
    | notas internas, e não altera o corpo armazenado no histórico.
    |
    | Placeholders no template: {name}, {first_name}, {message}
    |
    */

    'agent_signature' => [
        'enabled' => env('WHATSAPP_AGENT_SIGNATURE', true),
        'template' => env('WHATSAPP_AGENT_SIGNATURE_TEMPLATE', "*{name}*\n{message}"),
    ],

    'local_test_numbers' => array_values(array_filter(array_map(
        static fn (string $number): string => preg_replace('/\D+/', '', $number) ?? '',
        explode(',', (string) env('WHATSAPP_LOCAL_TEST_NUMBERS', '27981180125,45999178290'))
    ))),

    'api_url' => env('WHATSAPP_API_URL'),

    'api_token' => env('WHATSAPP_API_TOKEN'),

    'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET'),

    'webhook_url' => env('WHATSAPP_WEBHOOK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Evolution-API v2
    |--------------------------------------------------------------------------
    |
    | Configurações exclusivas do provedor Evolution-API.
    |
    | evolution_instance : nome da instância criada no painel Evolution
    | evolution_api_key  : chave de autenticação (header apikey)
    |
    | Quando WHATSAPP_PROVIDER=evolution, WHATSAPP_API_URL deve apontar
    | para a URL base do servidor Evolution (ex: https://evolution.amura.app).
    |
    */

    'evolution_instance' => env('WHATSAPP_EVOLUTION_INSTANCE'),
    'evolution_api_key' => env('WHATSAPP_EVOLUTION_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Chatbot
    |--------------------------------------------------------------------------
    */

    'chatbot' => [
        // Tempo em minutos antes de uma conversa inativa ser expirada
        'session_ttl_minutes' => (int) env('WHATSAPP_SESSION_TTL', 60),

        // Tenta identificar automaticamente o cliente pelo telefone recebido
        'auto_identify_company_by_phone' => env('WHATSAPP_AUTO_IDENTIFY_COMPANY_BY_PHONE', true),

        // Origin ID para tickets abertos via WhatsApp (ticketit_origin id=5)
        'origin_id' => (int) env('WHATSAPP_ORIGIN_ID', 5),

        // Legado/compatibilidade: tickets do bot entram sem agente, na fila do setor
        'default_agent_id' => env('WHATSAPP_DEFAULT_AGENT_ID'),
        'assign_default_agent' => env('WHATSAPP_ASSIGN_DEFAULT_AGENT', false),

        // Usuário autor/sistema para tickets abertos pelo bot quando não há agente
        'system_user_id' => env('WHATSAPP_SYSTEM_USER_ID'),

        // Status inicial dos tickets criados via WhatsApp
        'default_status_id' => (int) env('WHATSAPP_DEFAULT_STATUS_ID', 4),

        // Prioridade padrão
        'default_priority_id' => (int) env('WHATSAPP_DEFAULT_PRIORITY_ID', 1),

        // Áreas disponíveis para encaminhamento
        'routing_areas' => [
            '1' => 'Suporte Técnico',
            '2' => 'Financeiro',
            '3' => 'Comercial',
        ],

        // Departamentos destino para filas de tickets criados via WhatsApp
        'routing_departments' => [
            '1' => env('WHATSAPP_DEPARTMENT_SUPORTE_ID', 1),
            '2' => env('WHATSAPP_DEPARTMENT_FINANCEIRO_ID', 2),
            '3' => env('WHATSAPP_DEPARTMENT_COMERCIAL_ID', 3),
        ],

        'out_of_hours_in_tests' => env('WHATSAPP_OUT_OF_HOURS_IN_TESTS', false),

        // Categoria raiz padrão para tickets criados via WhatsApp
        'default_category_id' => env('WHATSAPP_CATEGORY_DEFAULT') ? (int) env('WHATSAPP_CATEGORY_DEFAULT') : null,

        // Categorias específicas por área escolhida no chatbot
        'categories_by_area' => [
            '1' => env('WHATSAPP_CATEGORY_SUPORTE') ? (int) env('WHATSAPP_CATEGORY_SUPORTE') : null,
            '2' => env('WHATSAPP_CATEGORY_FINANCEIRO') ? (int) env('WHATSAPP_CATEGORY_FINANCEIRO') : null,
            '3' => env('WHATSAPP_CATEGORY_COMERCIAL') ? (int) env('WHATSAPP_CATEGORY_COMERCIAL') : null,
        ],

        // Minutos sem interação em atendimento humano antes de liberar novo fluxo do bot
        'human_handoff_idle_minutes' => (int) env('WHATSAPP_HUMAN_HANDOFF_IDLE_MINUTES', 5),

        // Delay em minutos após encerramento do chamado antes de liberar o bot para novo atendimento
        'ticket_closed_delay_minutes' => (int) env('WHATSAPP_TICKET_CLOSED_DELAY_MINUTES', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mensagens do Chatbot
    |--------------------------------------------------------------------------
    */

    'messages' => [
        'menu' => env('WHATSAPP_MSG_MENU') ?:
            "Olá! 👋 Bem-vindo ao suporte *Amura*.\n\nComo posso te ajudar?\n\n1️⃣ Abrir um chamado\n2️⃣ Consultar status do meu chamado\n3️⃣ Falar com um atendente\n\nResponda com o número da opção.",
        'human_pending' => env('WHATSAPP_MSG_HUMAN_PENDING') ?:
            "✋ Certo! Estou te conectando com um de nossos atendentes.\n\nAguarde, em breve você será atendido. Nosso horário de atendimento é de segunda a sexta, das 8h às 18h.",
        'status_found' => env('WHATSAPP_MSG_STATUS_FOUND') ?:
            "📋 *Seu chamado mais recente:*\n\n🔢 Número: #{ticket_id}\n📝 Assunto: {subject}\n🔄 Status: *{status}*\n📅 Aberto em: {date}\n\nSe precisar de mais informações, fale com um atendente (opção 3).",
        'status_not_found' => env('WHATSAPP_MSG_STATUS_NOT_FOUND') ?:
            "ℹ️ Não encontramos chamados em seu nome.\n\nSe quiser abrir um chamado ou falar com um atendente, volte ao menu principal.",
        'greeting' => env('WHATSAPP_MSG_GREETING') ?:
            "Ótimo! Vamos abrir seu chamado.\n\n*Qual o seu nome?*",
        'ask_company' => env('WHATSAPP_MSG_ASK_COMPANY') ?:
            "Obrigado, *{name}*! 😊\n\n*Qual o nome da empresa?*",
        'ask_company_cnpj' => env('WHATSAPP_MSG_ASK_COMPANY_CNPJ') ?:
            "*Informe o CNPJ da empresa* para abrirmos um cadastro mínimo do chamado.\n_Pode digitar somente os números ou com pontuação (ex.: 12.345.678/0001-90)._",
        'ask_company_phone' => env('WHATSAPP_MSG_ASK_COMPANY_PHONE') ?:
            '*Informe o telefone com DDD* para contato.',
        'company_identified' => env('WHATSAPP_MSG_COMPANY_IDENTIFIED') ?:
            "Perfeito, *{name}*! 😊\n\nIdentifiquei sua empresa como *{company}* pelo número informado.",
        'ask_area' => env('WHATSAPP_MSG_ASK_AREA') ?:
            "Entendido! Em qual área você precisa de atendimento?\n\n1️⃣ Suporte Técnico\n2️⃣ Financeiro\n3️⃣ Comercial\n\nResponda com o número da opção.",
        'ask_problem' => env('WHATSAPP_MSG_ASK_PROBLEM') ?:
            "Área: *{area}* ✅\n\nDescreva o problema ou solicitação com o máximo de detalhes possível:",
        'ask_attachment' => env('WHATSAPP_MSG_ASK_ATTACHMENT') ?:
            "Você pode enviar anexos agora (imagens, documentos, áudios ou vídeos).\n\nQuando terminar, digite *confirmar* para abrir o chamado ou *cancelar* para recomeçar.",
        'confirm_summary' => env('WHATSAPP_MSG_CONFIRM') ?:
            "📋 *Resumo do chamado:*\n\n👤 Solicitante: {name}\n🏢 Empresa: {company}\n🧾 CNPJ: {cnpj}\n☎️ Telefone: {phone}\n📂 Área: {area}\n📝 Problema: {problem}\n📎 Anexos: {attachments}\n\n*1 - Confirmar* e abrir o chamado\n*2 - Cancelar* e recomeçar",
        'ticket_created' => env('WHATSAPP_MSG_CREATED') ?:
            "✅ *Chamado #{ticket_id} aberto com sucesso!*\n\nAssim que um agente for designado, você será notificado.\n\nObrigado por entrar em contato!",
        'company_not_found' => env('WHATSAPP_MSG_COMPANY_NOT_FOUND') ?:
            "⚠️ Não encontramos a empresa *{company}* em nossa base de clientes.\n\nVou coletar dados básicos para abrir o chamado via WhatsApp.",
        'out_of_hours' => env('WHATSAPP_MSG_OUT_OF_HOURS') ?:
            'Estamos fora do horário de expediente. Nosso atendimento é de segunda a sexta, das 08:30 às 18:00. Sua mensagem ficará registrada.',
        'invalid_option' => env('WHATSAPP_MSG_INVALID') ?:
            'Opção inválida. Por favor, responda com 1, 2 ou 3.',
        'cancelled' => env('WHATSAPP_MSG_CANCELLED') ?:
            'Atendimento cancelado. Digite qualquer mensagem para iniciar novamente.',
        'conversation_finished' => env('WHATSAPP_MSG_CONVERSATION_FINISHED') ?:
            "Seu atendimento foi finalizado com sucesso.\nConte conosco sempre que precisar novamente.",
        'error' => env('WHATSAPP_MSG_ERROR') ?:
            'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente em instantes.',

        // Fluxo: Cliente identificado
        'greeting_identified' => env('WHATSAPP_MSG_GREETING_IDENTIFIED') ?:
            "Oi 👋 Seja bem-vindo ao suporte da *Amura*!\nPara começar, pode me passar o *CNPJ* da sua empresa?\n_Pode digitar somente os números ou com pontuação (ex.: 12.345.678/0001-90)._",
        'greeting_by_phone' => env('WHATSAPP_MSG_GREETING_BY_PHONE') ?:
            "Oi 👋 Seja bem-vindo ao suporte da *Amura*!\n\nLocalizei seu cadastro vinculado a *{company}*. ✅\n\nPara registrar o atendimento, me informe seu *nome*.",
        'cnpj_located' => env('WHATSAPP_MSG_CNPJ_LOCATED') ?:
            "Perfeito! ✅ Localizei seu cadastro.\n\nAgora me informe seu *nome* para eu registrar o atendimento.",
        'sector_choice' => env('WHATSAPP_MSG_SECTOR_CHOICE') ?:
            "Obrigado, *{name}*! 😄\n\nCom qual setor você gostaria de falar hoje?\n\n1️⃣ Suporte técnico\n2️⃣ Financeiro\n3️⃣ Comercial\n\nDigite o número ou o nome da opção.",
        'problem_for_support' => env('WHATSAPP_MSG_PROBLEM_SUPPORT') ?:
            "Para {sector} ✅\n\nPara agilizar o atendimento, me conta o que você precisa — se quiser, pode enviar imagens 📸 ou documentos 📎",
        'problem_for_financeiro' => env('WHATSAPP_MSG_PROBLEM_FINANCEIRO') ?:
            "Perfeito! 💰\n\nVocê pode mandar uma mensagem direto para o nosso número do setor financeiro 📞 27 98132-4555,\nou, se preferir, posso abrir um chamado para que nossa equipe entre em contato com você.\n\nO que você prefere fazer?\n1️⃣ Abrir chamado para retorno\n2️⃣ Encerrar",
        'problem_for_comercial' => env('WHATSAPP_MSG_PROBLEM_COMERCIAL') ?:
            "Beleza! Nosso time Comercial pode te ajudar.\nVocê também pode ligar direto para 📞 27 98810-8533.\n\nO que você prefere fazer?\n1️⃣ Abrir chamado para retorno\n2️⃣ Encerrar",
        'goodbye' => env('WHATSAPP_MSG_GOODBYE') ?:
            "Tudo bem! Se precisar, é só voltar aqui.\nAté mais 👋",
        'session_expired' => env('WHATSAPP_MSG_SESSION_EXPIRED') ?:
            'Sua sessão foi encerrada por inatividade. Para continuar, é só mandar uma mensagem que começamos novamente! 👋',

        // Fluxo: Cliente não localizado
        'cnpj_not_found' => env('WHATSAPP_MSG_CNPJ_NOT_FOUND') ?:
            "Hmm... não encontrei esse CNPJ no nosso sistema 🤔\nMas sem problema, veja as opções:\n\n1️⃣ Falar com nosso time Comercial\n2️⃣ Digitar o CNPJ novamente\n3️⃣ Encerrar atendimento\n\nDigite o número ou o nome da opção.",
        'not_found_comercial' => env('WHATSAPP_MSG_NOT_FOUND_COMERCIAL') ?:
            "Beleza! Nosso time Comercial pode te ajudar.\nVocê também pode ligar direto para 📞 27 98810-8533.\n\nPara adiantar o processo, preciso de alguns dados rápidos:\nQual o seu *nome*?",
        'ask_company_not_found' => env('WHATSAPP_MSG_ASK_COMPANY_NOT_FOUND') ?:
            "Obrigado, *{name}*! 👍\n\nQual o nome da *empresa*?",
        'phone_choice' => env('WHATSAPP_MSG_PHONE_CHOICE') ?:
            "Perfeito! Agora sobre o telefone:\nQuer usar o número que você está falando comigo agora para contato,\nou prefere informar outro?\n\n1️⃣ Usar número atual\n2️⃣ Informar outro",
        'phone_registered' => env('WHATSAPP_MSG_PHONE_REGISTERED') ?:
            'Vou registrar esse número para retorno. 👍',
        'ask_phone_other' => env('WHATSAPP_MSG_ASK_PHONE_OTHER') ?:
            'Sem problema! Me passa o *telefone com DDD* que você quer usar para contato.',
        'not_found_acknowledged' => env('WHATSAPP_MSG_NOT_FOUND_ACK') ?:
            'Show! Já anotei tudo.',
        'not_found_default_problem' => env('WHATSAPP_MSG_NOT_FOUND_DEFAULT_PROBLEM') ?:
            'Cliente não cadastrado — solicita retorno do time Comercial.',
        'summary_not_found' => env('WHATSAPP_MSG_SUMMARY_NOT_FOUND') ?:
            "📋 Aqui está o resumo do seu chamado:\n\n👤 Nome: {name}\n🏢 Empresa: {company}\n🧾 CNPJ: {cnpj}\n☎️ Telefone: {phone}\n📂 Área: Comercial\n\n*1 - Confirmar* o atendimento\n*2 - Cancelar* e recomeçar",
    ],

];
