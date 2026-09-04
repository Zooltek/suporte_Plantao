<?php

namespace App\Models;

use App\Models\Helpdesk\Ticket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketFile extends Model
{
    /**
     * Diretório base para armazenamento de anexos.
     */
    const STORAGE_PATH = 'attachment/';

    /**
     * Nome da tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'ticket_files';

    /**
     * Indica que o modelo não deve ter timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Atributos preenchíveis em massa.
     *
     * @var array
     */
    protected $fillable = [
        'ticket_id',
        'name',
        'extension',
        'path',
    ];

    /**
     * Acessador para a URL completa do arquivo.
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => asset(self::STORAGE_PATH . $this->name . '.' . $this->extension),
        );
    }

    /**
     * Relacionamento: arquivo pertence a um ticket.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Gera o HTML para exibir o arquivo.
     *
     * @return string
     */
    public function getHtmlPortrait(): string
    {
        $url = $this->url;
        $ext = strtolower($this->extension);
        $imageExtensions = ['png', 'jpg', 'jpeg', 'bmp', 'webp'];

        if (in_array($ext, $imageExtensions)) {
            return sprintf(
                '<a href="%s" target="_blank"><img src="%s" class="attachment-portrait" alt="Anexo"></a>',
                $url,
                $url
            );
        }

        if (in_array($ext, ['rar', 'zip', 'pdf'])) {
            $label = ($ext === 'pdf') ? 'PDF' : 'Arquivo';
            return sprintf(
                '<a href="%s" download><div class="attachment-portrait">%s</div></a>',
                $url,
                $label
            );
        }

        return '';
    }
}
