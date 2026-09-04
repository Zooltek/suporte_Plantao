<?php

namespace App\Traits\Ticket;

use Illuminate\Support\Str;

trait ContentEllipse
{
    /**
     * Resume o conteúdo de um comentário ou ticket se ele for muito longo.
     */
    public function getShortContent(int $maxlength = 50, string $attr = 'content'): string
    {
        $content = (string) $this->{$attr};
        $result = $content;

        if (Str::length($content) > $maxlength) {
            $result = Str::limit($content, $maxlength, '...');
        }

        return $result;
    }
}
