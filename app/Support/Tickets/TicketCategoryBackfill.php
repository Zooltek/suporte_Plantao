<?php

namespace App\Support\Tickets;

use Illuminate\Database\ConnectionInterface;

class TicketCategoryBackfill
{
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {}

    /**
     * @return array{snapshotted:int,normalized:int,cleared_invalid_subcategories:int}
     */
    public function run(): array
    {
        return [
            'snapshotted' => $this->snapshotLegacyCategoryIds(),
            'normalized' => $this->normalizeParentCategories(),
            'cleared_invalid_subcategories' => $this->clearInvalidRootSubcategories(),
        ];
    }

    public function snapshotLegacyCategoryIds(): int
    {
        if (! $this->hasLegacyColumn()) {
            return 0;
        }

        $updated = 0;

        $this->connection->table('ticketit')
            ->select(['id', 'category_id', 'legacy_ticket_category_id'])
            ->orderBy('id')
            ->chunkById(100, function ($tickets) use (&$updated): void {
                foreach ($tickets as $ticket) {
                    if ($ticket->legacy_ticket_category_id !== null) {
                        continue;
                    }

                    $this->connection->table('ticketit')
                        ->where('id', $ticket->id)
                        ->update(['legacy_ticket_category_id' => $ticket->category_id]);

                    $updated++;
                }
            });

        return $updated;
    }

    public function normalizeParentCategories(): int
    {
        $updated = 0;

        $this->iterateTicketsWithSubcategory(function (object $ticket, array $subcategories) use (&$updated): void {
            $parentId = (int) ($subcategories[$ticket->sub_category_id] ?? 0);

            if ($parentId <= 0 || (int) $ticket->category_id === $parentId) {
                return;
            }

            $this->connection->table('ticketit')
                ->where('id', $ticket->id)
                ->update(['category_id' => $parentId]);

            $updated++;
        });

        return $updated;
    }

    public function clearInvalidRootSubcategories(): int
    {
        $updated = 0;

        $this->iterateTicketsWithSubcategory(function (object $ticket, array $subcategories) use (&$updated): void {
            $parentId = (int) ($subcategories[$ticket->sub_category_id] ?? 0);

            if ($parentId !== 0) {
                return;
            }

            $this->connection->table('ticketit')
                ->where('id', $ticket->id)
                ->update(['sub_category_id' => null]);

            $updated++;
        });

        return $updated;
    }

    private function hasLegacyColumn(): bool
    {
        return $this->connection
            ->getSchemaBuilder()
            ->hasColumn('ticketit', 'legacy_ticket_category_id');
    }

    /**
     * @param  callable(object,array<int,int>): void  $callback
     */
    private function iterateTicketsWithSubcategory(callable $callback): void
    {
        $this->connection->table('ticketit')
            ->select(['id', 'category_id', 'sub_category_id'])
            ->whereNotNull('sub_category_id')
            ->orderBy('id')
            ->chunkById(100, function ($tickets) use ($callback): void {
                $subCategoryIds = collect($tickets)
                    ->pluck('sub_category_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                if ($subCategoryIds === []) {
                    return;
                }

                $subcategories = $this->connection->table('solutions_category')
                    ->whereIn('category_id', $subCategoryIds)
                    ->pluck('parent_id', 'category_id')
                    ->mapWithKeys(fn ($parentId, $categoryId) => [(int) $categoryId => (int) $parentId])
                    ->all();

                foreach ($tickets as $ticket) {
                    $callback($ticket, $subcategories);
                }
            });
    }
}
