<?php

namespace App\Services\Ticket;

use App\Contracts\Repositories\AttachmentRepositoryInterface;
use App\Models\Ticket\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    private const DISK = 'public';
    private const BASE_PATH = 'tickets/attachments';

    public function __construct(
        private readonly AttachmentRepositoryInterface $repository,
    ) {}

    /**
     * Faz upload de um arquivo e cria o registro no banco.
     * A validação de mimes/tamanho é responsabilidade do controller (FormRequest).
     */
    public function upload(UploadedFile $file, ?int $ticketId = null): Attachment
    {
        // A05/OWASP: extensão detectada pelo conteúdo real (finfo), não pelo nome do cliente.
        $extension = $file->extension() ?: 'bin';
        $safeName  = Str::uuid() . '.' . $extension;
        $diskPath  = self::BASE_PATH . '/' . $safeName;

        Storage::disk(self::DISK)->putFileAs(self::BASE_PATH, $file, $safeName);

        $attachment = $this->repository->create([
            'name'          => $safeName,
            'original_name' => $file->getClientOriginalName(),
            'mime'          => $extension,
            'disk_path'     => $diskPath,
            'size'          => $file->getSize(),
            'author_id'     => Auth::id(),
            'ticket_id'     => $ticketId,
            'status'        => 1,
        ]);

        Log::info('Anexo enviado', [
            'attachment_id' => $attachment->id,
            'ticket_id'     => $ticketId,
            'user_id'       => Auth::id(),
            'mime'          => $extension,
            'size'          => $file->getSize(),
        ]);

        return $attachment;
    }

    /**
     * Remove o arquivo do disco e o registro do banco.
     */
    public function delete(Attachment $attachment): void
    {
        if ($attachment->disk_path && Storage::disk(self::DISK)->exists($attachment->disk_path)) {
            Storage::disk(self::DISK)->delete($attachment->disk_path);
        } elseif ($attachment->name) {
            // compatibilidade com registros sem disk_path
            $legacyPath = self::BASE_PATH . '/' . $attachment->name;
            Storage::disk(self::DISK)->delete($legacyPath);
        }

        Log::info('Anexo removido', [
            'attachment_id' => $attachment->id,
            'ticket_id'     => $attachment->ticket_id,
            'user_id'       => Auth::id(),
        ]);

        $this->repository->delete($attachment);
    }

    /**
     * Retorna o caminho absoluto para leitura direta no browser.
     */
    public function getStoragePath(Attachment $attachment): string
    {
        $path = $attachment->disk_path ?: (self::BASE_PATH . '/' . $attachment->name);
        return Storage::disk(self::DISK)->path($path);
    }

    /**
     * Retorna a URL pública para exibição no browser.
     */
    public function getPublicUrl(Attachment $attachment): string
    {
        $path = $attachment->disk_path ?: (self::BASE_PATH . '/' . $attachment->name);
        return Storage::disk(self::DISK)->url($path);
    }

    /**
     * Verifica se a extensão permite visualização direta no browser.
     */
    public function isViewableInBrowser(Attachment $attachment): bool
    {
        $viewable = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico', 'mp4', 'webm', 'ogg', 'txt'];
        return in_array(strtolower($attachment->mime), $viewable);
    }
}
