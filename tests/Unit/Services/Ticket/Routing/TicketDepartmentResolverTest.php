<?php

use App\Models\Category;
use App\Models\CategoryDescription;
use App\Models\Department;
use App\Models\User;
use App\Services\Ticket\Routing\TicketDepartmentResolver;
use App\Services\Ticket\Routing\TicketDepartmentRoutingIntent;

function tdr_department(string $name, array $attrs = []): Department
{
    return Department::factory()->create(array_merge(['name' => $name], $attrs));
}

function tdr_category(?int $departmentId = null): Category
{
    $category = Category::factory()->create([
        'parent_id' => 0,
        'priority' => 'low',
        'department_id' => $departmentId,
    ]);
    CategoryDescription::factory()->create(['category_id' => $category->category_id]);

    return $category;
}

function tdr_agent(?int $departmentId): User
{
    return User::factory()->create([
        'department_id' => $departmentId,
    ]);
}

function tdr_resolver(): TicketDepartmentResolver
{
    return new TicketDepartmentResolver;
}

describe('TicketDepartmentResolver — precedência', function () {

    it('override manual vence todos os demais sinais', function () {
        $explicit = tdr_department('Explicit Dept');
        $category = tdr_category(tdr_department('Comercial')->id);
        $channel = tdr_department('Canal Dept');
        $agent = tdr_agent(tdr_department('Agent Dept')->id);

        $result = tdr_resolver()->resolve(new TicketDepartmentRoutingIntent(
            explicitDepartmentId: $explicit->id,
            categoryId: $category->category_id,
            channelDepartmentId: $channel->id,
            agentId: $agent->id,
        ));

        expect($result)->toBe($explicit->id);
    });

    it('categoria vence canal e agente quando não há override', function () {
        $comercial = tdr_department('Comercial');
        $category = tdr_category($comercial->id);
        $channel = tdr_department('Canal Dept');
        $agent = tdr_agent(tdr_department('Suporte Agent')->id);

        $result = tdr_resolver()->resolve(new TicketDepartmentRoutingIntent(
            categoryId: $category->category_id,
            channelDepartmentId: $channel->id,
            agentId: $agent->id,
        ));

        expect($result)->toBe($comercial->id);
    });

    it('canal vence agente quando categoria não define departamento', function () {
        $channel = tdr_department('Canal Vencedor');
        $category = tdr_category(null);
        $agent = tdr_agent(tdr_department('Agent Loser')->id);

        $result = tdr_resolver()->resolve(new TicketDepartmentRoutingIntent(
            categoryId: $category->category_id,
            channelDepartmentId: $channel->id,
            agentId: $agent->id,
        ));

        expect($result)->toBe($channel->id);
    });

    it('agente é usado quando categoria e canal não definem', function () {
        $agentDept = tdr_department('Agent Dept');
        $agent = tdr_agent($agentDept->id);

        $result = tdr_resolver()->resolve(new TicketDepartmentRoutingIntent(
            agentId: $agent->id,
        ));

        expect($result)->toBe($agentDept->id);
    });

    it('fallback Suporte quando nada se aplica e allowSupportFallback=true', function () {
        Department::query()->whereRaw('LOWER(name) like ?', ['%suporte%'])->delete();
        $support = tdr_department('Suporte Técnico Teste');

        $result = tdr_resolver()->resolve(new TicketDepartmentRoutingIntent);

        expect($result)->toBe($support->id);
    });

    it('retorna null quando nada se aplica e allowSupportFallback=false', function () {
        $result = tdr_resolver()->resolve(new TicketDepartmentRoutingIntent(
            allowSupportFallback: false,
        ));

        expect($result)->toBeNull();
    });

    it('agente sem departamento cai no fallback Suporte', function () {
        Department::query()->whereRaw('LOWER(name) like ?', ['%suporte%'])->delete();
        $support = tdr_department('Suporte Técnico Teste');
        $agent = tdr_agent(null);

        $result = tdr_resolver()->resolve(new TicketDepartmentRoutingIntent(
            agentId: $agent->id,
        ));

        expect($result)->toBe($support->id);
    });

    it('categoria com department_id NULL não interrompe a precedência', function () {
        $category = tdr_category(null);
        $channel = tdr_department('Canal Backup');

        $result = tdr_resolver()->resolve(new TicketDepartmentRoutingIntent(
            categoryId: $category->category_id,
            channelDepartmentId: $channel->id,
        ));

        expect($result)->toBe($channel->id);
    });

    it('subcategoria vence a categoria pai quando ambas definem departamento', function () {
        $parentDept = tdr_department('Parent Dept');
        $subDept = tdr_department('Sub Dept Mais Específico');
        $parent = tdr_category($parentDept->id);
        $sub = tdr_category($subDept->id);

        $result = tdr_resolver()->resolve(new TicketDepartmentRoutingIntent(
            subCategoryId: $sub->category_id,
            categoryId: $parent->category_id,
        ));

        expect($result)->toBe($subDept->id);
    });

    it('cai na categoria pai quando subcategoria não tem departamento', function () {
        $parentDept = tdr_department('Parent Wins');
        $parent = tdr_category($parentDept->id);
        $sub = tdr_category(null);

        $result = tdr_resolver()->resolve(new TicketDepartmentRoutingIntent(
            subCategoryId: $sub->category_id,
            categoryId: $parent->category_id,
        ));

        expect($result)->toBe($parentDept->id);
    });

});

describe('TicketDepartmentResolver — caching dentro da request', function () {

    it('reaproveita lookups de categoria entre chamadas no mesmo Resolver', function () {
        $dept = tdr_department('Cache Test');
        $category = tdr_category($dept->id);

        $resolver = tdr_resolver();

        $first = $resolver->resolve(new TicketDepartmentRoutingIntent(
            categoryId: $category->category_id,
        ));

        Category::query()->whereKey($category->category_id)->update(['department_id' => null]);

        $second = $resolver->resolve(new TicketDepartmentRoutingIntent(
            categoryId: $category->category_id,
        ));

        expect($first)->toBe($dept->id);
        expect($second)->toBe($dept->id);
    });

});
