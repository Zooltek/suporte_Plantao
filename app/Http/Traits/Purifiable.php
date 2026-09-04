<?php

namespace App\Http\Traits;

use App\Models\Helpdesk\Ticketit\Setting;
use Mews\Purifier\Facades\Purifier;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

trait Purifiable
{
    /**
     * Updates the content and html attribute of the given model.
     */
    public function setPurifiedContent(string $rawHtml): Model
    {
        Log::alert("PASSOU");
        
        // Limpa removendo todas as tags HTML
        $this->content = Purifier::clean($rawHtml, ['HTML.Allowed' => '']);
        
        // Limpa usando a configuração personalizada do banco de dados
        $this->html = Purifier::clean($rawHtml, Setting::grab('purifier_config'));

        return $this;
    }

    /**
     * Updates the content and html attribute for agents.
     */
    public function setPurifiedContentByAgent(string $rawHtml): Model
    {
        $this->content = Purifier::clean($rawHtml, ['HTML.Allowed' => '']);
        
        // Mantém o HTML original (Cuidado: verifique se confia totalmente na entrada do Agent)
        $this->html = $rawHtml;

        return $this;
    }
}
