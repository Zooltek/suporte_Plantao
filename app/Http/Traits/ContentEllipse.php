<?php

namespace App\Http\Traits;

trait ContentEllipse
{
    /**
     * Cuts the content of a comment or a ticket content if it's too long.
     *
     * @param int $maxlength Maximum length of the content
     * @param string $attr   Attribute name to shorten
     *
     * @return string
     */
    public function getShortContent(int $maxlength = 50, string $attr = 'content'): string
    {
        $content = $this->{$attr} ?? '';

        if (mb_strlen($content) > $maxlength) {
            return mb_substr($content, 0, $maxlength) . '...';
        }

        return $content;
    }
}
