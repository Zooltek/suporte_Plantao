<?php

use App\Models\Knowledge\KnowledgeArticle;
use App\Models\Schedule;
use App\Models\Schedule\Record;
use App\Models\User;
use App\Policies\KnowledgeArticlePolicy;
use App\Policies\RecordPolicy;
use App\Policies\SchedulePolicy;

function policyUser(array $attributes = []): User
{
    $user = new User($attributes);
    $user->id = $attributes['id'] ?? 1;

    return $user;
}

function policySchedule(int $agentId): Schedule
{
    $schedule = new Schedule();
    $schedule->agent_id = $agentId;

    return $schedule;
}

describe('KnowledgeArticlePolicy::delete', function () {
    it('permite exclusão para admin e para o autor agente', function () {
        $policy = new KnowledgeArticlePolicy();
        $article = new KnowledgeArticle(['author_id' => 10]);

        $admin = policyUser(['id' => 1, 'ticketit_admin' => true, 'ticketit_agent' => true]);
        $author = policyUser(['id' => 10, 'ticketit_agent' => true]);

        expect($policy->delete($admin, $article))->toBeTrue()
            ->and($policy->delete($author, $article))->toBeTrue();
    });

    it('bloqueia agente que não é autor e usuário sem perfil de agente', function () {
        $policy = new KnowledgeArticlePolicy();
        $article = new KnowledgeArticle(['author_id' => 10]);

        $otherAgent = policyUser(['id' => 20, 'ticketit_agent' => true]);
        $customer = policyUser(['id' => 30, 'ticketit_agent' => false, 'ticketit_admin' => false]);

        expect($policy->delete($otherAgent, $article))->toBeFalse()
            ->and($policy->delete($customer, $article))->toBeFalse();
    });
});

describe('RecordPolicy', function () {
    it('permite view/create para agentes autenticados', function () {
        $policy = new RecordPolicy();
        $agent = policyUser(['id' => 5, 'ticketit_agent' => true]);
        $record = new Record(['agent_id' => 5]);

        expect($policy->view($agent, $record))->toBeTrue()
            ->and($policy->create($agent))->toBeTrue();
    });

    it('permite update ao admin e ao agente responsável, mas bloqueia terceiros', function () {
        $policy = new RecordPolicy();
        $record = new Record(['agent_id' => 5]);

        $admin = policyUser(['id' => 1, 'ticketit_admin' => true, 'ticketit_agent' => true]);
        $owner = policyUser(['id' => 5, 'ticketit_agent' => true]);
        $otherAgent = policyUser(['id' => 6, 'ticketit_agent' => true]);

        expect($policy->update($admin, $record))->toBeTrue()
            ->and($policy->update($owner, $record))->toBeTrue()
            ->and($policy->update($otherAgent, $record))->toBeFalse();
    });

    it('restringe delete apenas ao admin', function () {
        $policy = new RecordPolicy();
        $record = new Record(['agent_id' => 5]);

        $admin = policyUser(['id' => 1, 'ticketit_admin' => true, 'ticketit_agent' => true]);
        $agent = policyUser(['id' => 5, 'ticketit_agent' => true]);

        expect($policy->delete($admin, $record))->toBeTrue()
            ->and($policy->delete($agent, $record))->toBeFalse();
    });
});

describe('SchedulePolicy', function () {
    it('permite viewAny, view e create para agentes autenticados', function () {
        $policy = new SchedulePolicy();
        $agent = policyUser(['id' => 8, 'ticketit_agent' => true]);
        $schedule = policySchedule(8);

        expect($policy->viewAny($agent))->toBeTrue()
            ->and($policy->view($agent, $schedule))->toBeTrue()
            ->and($policy->create($agent))->toBeTrue();
    });

    it('permite update/finalize ao admin e ao agente responsável', function () {
        $policy = new SchedulePolicy();
        $schedule = policySchedule(8);

        $admin = policyUser(['id' => 1, 'ticketit_admin' => true, 'ticketit_agent' => true]);
        $owner = policyUser(['id' => 8, 'ticketit_agent' => true]);
        $otherAgent = policyUser(['id' => 9, 'ticketit_agent' => true]);

        expect($policy->update($admin, $schedule))->toBeTrue()
            ->and($policy->finalize($admin, $schedule))->toBeTrue()
            ->and($policy->update($owner, $schedule))->toBeTrue()
            ->and($policy->finalize($owner, $schedule))->toBeTrue()
            ->and($policy->update($otherAgent, $schedule))->toBeFalse()
            ->and($policy->finalize($otherAgent, $schedule))->toBeFalse();
    });

    it('restringe delete e confirm apenas ao admin', function () {
        $policy = new SchedulePolicy();
        $schedule = policySchedule(8);

        $admin = policyUser(['id' => 1, 'ticketit_admin' => true, 'ticketit_agent' => true]);
        $agent = policyUser(['id' => 8, 'ticketit_agent' => true]);

        expect($policy->delete($admin, $schedule))->toBeTrue()
            ->and($policy->confirm($admin, $schedule))->toBeTrue()
            ->and($policy->delete($agent, $schedule))->toBeFalse()
            ->and($policy->confirm($agent, $schedule))->toBeFalse();
    });
});
