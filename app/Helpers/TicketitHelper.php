<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class TicketitHelper
{
    /**
     * Ordena coleções de forma dinâmica.
     * Substitui o antigo sortArray do ToolsController.
     */
    public static function sortData(iterable $data, string $field, string $type = 'desc'): Collection
    {
        $collection = collect($data);

        return $type === 'desc'
            ? $collection->sortByDesc($field)
            : $collection->sortBy($field);
    }

    /**
     * Verifica se a URL atual corresponde ao padrão.
     */
    public static function isCurrentUrl(string $pattern): bool
    {
        return Request::fullUrlIs($pattern);
    }
}
