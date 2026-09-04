<?php

use App\Support\Tickets\TicketCategoryBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ticketit', 'legacy_ticket_category_id')) {
            Schema::table('ticketit', function (Blueprint $table) {
                $table->unsignedBigInteger('legacy_ticket_category_id')
                    ->nullable()
                    ->after('category_id');
            });
        }

        (new TicketCategoryBackfill(DB::connection()))->run();

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $unresolvedCategories = DB::table('ticketit as tickets')
            ->leftJoin('solutions_category as categories', 'categories.category_id', '=', 'tickets.category_id')
            ->whereNull('categories.category_id')
            ->count();

        if ($unresolvedCategories > 0) {
            throw new RuntimeException(
                sprintf(
                    'Não foi possível alinhar o domínio de categorias. %d ticket(s) ainda possuem category_id fora de solutions_category. Revise legacy_ticket_category_id antes de migrar.',
                    $unresolvedCategories
                )
            );
        }

        $this->swapMysqlCategoryForeignKey('solutions_category', 'category_id');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            if (Schema::hasColumn('ticketit', 'legacy_ticket_category_id')) {
                DB::table('ticketit')
                    ->whereNotNull('legacy_ticket_category_id')
                    ->update(['category_id' => DB::raw('legacy_ticket_category_id')]);
            }

            $this->swapMysqlCategoryForeignKey('ticketit_categories', 'id');
        }

        if (Schema::hasColumn('ticketit', 'legacy_ticket_category_id')) {
            Schema::table('ticketit', function (Blueprint $table) {
                $table->dropColumn('legacy_ticket_category_id');
            });
        }
    }

    private function swapMysqlCategoryForeignKey(string $referencedTable, string $referencedColumn): void
    {
        $constraintExists = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('CONSTRAINT_NAME', 'ticketit_category_id_foreign')
            ->exists();

        if ($constraintExists) {
            DB::statement('ALTER TABLE `ticketit` DROP FOREIGN KEY `ticketit_category_id_foreign`');
        }

        DB::statement(sprintf(
            'ALTER TABLE `ticketit` ADD CONSTRAINT `ticketit_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `%s` (`%s`)',
            $referencedTable,
            $referencedColumn
        ));
    }
};
