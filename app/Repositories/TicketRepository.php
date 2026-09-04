<?php

namespace App\Repositories;

use App\Contracts\Repositories\TicketRepositoryInterface;
use App\Models\Category;
use App\Models\Company;
use App\Models\Tasks\Task;
use App\Models\Ticket\Attachment;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketExtraCategory;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TicketRepository implements TicketRepositoryInterface
{
    /**
     * TTL padrão para cache de status/categorias que raramente mudam.
     */
    private const STATUS_CACHE_TTL = 3600;

    // -------------------------------------------------------------------------
    // Category
    // -------------------------------------------------------------------------

    public function findCategoryById(int $id): ?Category
    {
        return Category::with('description')
            ->where('category_id', $id)
            ->first();
    }

    // -------------------------------------------------------------------------
    // Company
    // -------------------------------------------------------------------------

    public function findCompanyOrFail(int $id): Company
    {
        return Company::findOrFail($id);
    }

    // -------------------------------------------------------------------------
    // Ticket persistence
    // -------------------------------------------------------------------------

    public function save(Ticket $ticket): void
    {
        $ticket->save();
    }

    public function updateTicket(Ticket $ticket, array $attributes): void
    {
        $ticket->update($attributes);
    }

    // -------------------------------------------------------------------------
    // Extra Categories (sync = delete + recreate)
    // -------------------------------------------------------------------------

    public function syncExtraCategories(Ticket $ticket, array $extras): void
    {
        $ticket->extraCategories()->delete();

        foreach ($extras as $extra) {
            $catId = (int) ($extra['category_id'] ?? 0);
            $subCatId = (int) ($extra['sub_category_id'] ?? 0);

            if ($catId && $subCatId) {
                TicketExtraCategory::create([
                    'ticket_id' => $ticket->id,
                    'category_id' => $catId,
                    'sub_category_id' => $subCatId,
                ]);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Attachments
    // -------------------------------------------------------------------------

    public function updateAttachments(array $attachmentIds, int $ticketId): void
    {
        if (empty($attachmentIds)) {
            return;
        }

        Attachment::whereIn('id', $attachmentIds)->update(['ticket_id' => $ticketId]);
    }

    // -------------------------------------------------------------------------
    // Task
    // -------------------------------------------------------------------------

    public function createTask(array $data): Task
    {
        return Task::create($data);
    }

    // -------------------------------------------------------------------------
    // Status helpers (com cache para evitar query repetida por request)
    // -------------------------------------------------------------------------

    public function getSolicitacaoStatusId(): int
    {
        return (int) Cache::remember(
            'ticket.status_id.solicitacao',
            self::STATUS_CACHE_TTL,
            static fn () => DB::table('ticketit_statuses')
                ->where('name', 'Solicitação')
                ->value('id')
        );
    }

    // -------------------------------------------------------------------------
    // Status flags (encapsula static calls do Model Status)
    // -------------------------------------------------------------------------

    public function isStatusTerminal(int $statusId): bool
    {
        return Status::isTerminal($statusId);
    }

    public function isStatusRequiresSchedule(int $statusId): bool
    {
        return Status::requiresSchedule($statusId);
    }

    public function isStatusRequiresSolution(int $statusId): bool
    {
        return Status::requiresSolution($statusId);
    }

    // -------------------------------------------------------------------------
    // Transaction
    // -------------------------------------------------------------------------

    public function transaction(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}
