<?php

namespace App\Http\Controllers\API\V1\Tickets;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Tickets\AttachmentResource;
use App\Models\Ticket\Attachment;
use App\Services\Ticket\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttachmentController extends Controller
{
    use AuthorizesTicketAccess;

    public function __construct(
        private readonly AttachmentService $attachmentService
    ) {}

    /**
     * Lista os anexos de um ticket.
     */
    public function index(int $ticket): AnonymousResourceCollection
    {
        $ticket = $this->authorizeTicketAccess($ticket);

        $attachments = Attachment::where('ticket_id', $ticket->id)
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        return AttachmentResource::collection($attachments);
    }

    /**
     * Faz upload de um arquivo vinculado a um ticket (ou avulso para associação posterior).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB — limite razoável para anexos de suporte (A03/OWASP)
                'mimes:jpg,jpeg,png,gif,bmp,pdf,txt,docx,xlsx,csv,zip,rar,7z,mp4',
            ],
            'ticket_id' => ['nullable', 'integer', 'exists:ticketit,id'],
        ]);

        $ticketId = $request->integer('ticket_id') ?: null;

        if ($ticketId !== null) {
            $this->authorizeTicketAccess($ticketId);
        }

        $attachment = $this->attachmentService->upload(
            $request->file('file'),
            $ticketId
        );

        return (new AttachmentResource($attachment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Exibe/serve um arquivo diretamente no browser quando possível.
     * A01/OWASP: verifica que o usuário autenticado pertence ao ticket do anexo.
     */
    public function show(Attachment $attachment): BinaryFileResponse|StreamedResponse
    {
        $this->authorizeAttachmentAccess($attachment);

        $path = $this->attachmentService->getStoragePath($attachment);

        abort_unless(file_exists($path), 404, 'Arquivo não encontrado.');

        $mime = mime_content_type($path) ?: 'application/octet-stream';

        if ($this->attachmentService->isViewableInBrowser($attachment)) {
            return response()->file($path, [
                'Content-Type'        => $mime,
                'Content-Disposition' => 'inline; filename="' . ($attachment->original_name ?? $attachment->name) . '"',
            ]);
        }

        return response()->download($path, $attachment->original_name ?? $attachment->name);
    }

    /**
     * Remove um anexo do disco e do banco.
     * A01/OWASP: verifica que o usuário autenticado pertence ao ticket do anexo.
     */
    public function destroy(Attachment $attachment): JsonResponse
    {
        $this->authorizeAttachmentAccess($attachment);

        $this->attachmentService->delete($attachment);

        return response()->json(['message' => 'Anexo removido.']);
    }

    /**
     * Verifica que o usuário tem acesso ao ticket dono do anexo.
     * Admins têm acesso irrestrito; agentes apenas aos seus próprios tickets.
     */
    private function authorizeAttachmentAccess(Attachment $attachment): void
    {
        $user = Auth::guard('admin')->user() ?? Auth::user();

        if ($user?->ticketit_admin) {
            return; // admin vê tudo
        }

        abort_if(
            ! $attachment->ticket_id,
            403,
            'Acesso não autorizado a este anexo.'
        );

        $this->authorizeTicketAccess((int) $attachment->ticket_id);
    }
}
