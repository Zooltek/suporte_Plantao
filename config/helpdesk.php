<?php

return [
    'chat' => [
        'origin_id' => (int) env('HELPDESK_CHAT_ORIGIN_ID', 2),
        'default_status_id' => (int) env('HELPDESK_CHAT_DEFAULT_STATUS_ID', 4),
        'default_priority_id' => (int) env('HELPDESK_CHAT_DEFAULT_PRIORITY_ID', 1),
        'default_category_id' => (int) env('HELPDESK_CHAT_DEFAULT_CATEGORY_ID', 1),
        'fallback_company_id' => (int) env('HELPDESK_CHAT_FALLBACK_COMPANY_ID', 1),
        'expire_hours' => (int) env('HELPDESK_CHAT_EXPIRE_HOURS', 12),
    ],
];
