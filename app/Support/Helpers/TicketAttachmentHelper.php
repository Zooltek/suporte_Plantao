<?php

namespace App\Support\Helpers;

class TicketAttachmentHelper
{
    public static function count(?array $attachments): int
    {
        return is_array($attachments) ? count($attachments) : 0;
    }
}
