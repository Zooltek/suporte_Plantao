<?php

use App\Models\Category;
use App\Models\CategoryDescription;
use App\Models\Company;
use App\Models\Ticket\Origin;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;

describe('TicketFactory — não deixa categorias órfãs', function () {

    it('quando category_id é fornecido explicitamente, NÃO cria Category extra', function () {
        $author = User::factory()->admin()->create();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $origin = Origin::factory()->create();
        $status = Status::factory()->create();
        $cat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
        CategoryDescription::factory()->create(['category_id' => $cat->category_id]);

        $categoriesBefore = Category::count();

        Ticket::factory()->create([
            'author_id' => $author->id,
            'user_id' => $author->id,
            'company_id' => $company->id,
            'status_id' => $status->id,
            'origin_id' => $origin->id,
            'category_id' => $cat->category_id,
        ]);

        $categoriesAfter = Category::count();
        expect($categoriesAfter)->toBe($categoriesBefore);
    });

    it('toda categoria criada pelo TicketFactory tem CategoryDescription', function () {
        $orphansBefore = Category::query()
            ->whereDoesntHave('description')
            ->count();

        // Cria 3 tickets sem fornecer category_id — força a factory a criar
        Ticket::factory()->count(3)->create();

        $orphansAfter = Category::query()
            ->whereDoesntHave('description')
            ->count();

        expect($orphansAfter)->toBe($orphansBefore);
    });

});
