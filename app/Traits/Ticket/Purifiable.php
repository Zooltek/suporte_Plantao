<?php

namespace App\Traits\Ticket;

use App\Models\Ticket\Setting;
use Mews\Purifier\Facades\Purifier;

trait Purifiable
{
    /**
     * Atualiza os atributos content e html do model limpando o conteúdo.
     */
    public function setPurifiedContent(string $rawHtml): self
    {
        $this->content = Purifier::clean($rawHtml, [
            'HTML.Allowed' => '',
        ]);

        $this->html = Purifier::clean($rawHtml, Setting::grab('purifier_config') ?? []);
        
        return $this;
    }

    /**
     * Atualiza os atributos para conteúdo vindo de um Agente.
     */
    public function setPurifiedContentByAgent(string $rawHtml): self
    {
        $this->content = Purifier::clean($rawHtml, [
            'HTML.Allowed' => '',
        ]);

        $this->html = $rawHtml;

        return $this;
    }
}
