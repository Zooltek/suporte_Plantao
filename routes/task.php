<?php

use App\Http\Controllers\API\V1\Tasks\Changelog\VersionController as ChangelogVersionController;
use App\Http\Controllers\API\V1\Tasks\ChangelogController;
use App\Http\Controllers\API\V1\Tasks\CommentController;
use App\Http\Controllers\API\V1\Tasks\LabelController;
use App\Http\Controllers\API\V1\Tasks\NotificationController;
use App\Http\Controllers\API\V1\Tasks\ProjectController;
use App\Http\Controllers\API\V1\Tasks\SoftwareController as TaskSoftwareController;
use App\Http\Controllers\API\V1\Tasks\TaskController;
use App\Http\Controllers\API\V1\Tasks\UserProjectController;
use App\Http\Controllers\API\V1\Tasks\VersionController as TaskVersionController;
use Illuminate\Support\Facades\Route;

/**
 * API de Tarefas — autenticada por token (aplicativo externo / mobile nativo).
 * Rotas web admin:    routes/admin.php    → /admin/tasks/*   (guard: admin)
 * Inbox web staff:    routes/web.php      → /tasks           (guard: admin)
 */
Route::middleware(['api', 'token', 'auth', 'throttle:tasks:api'])
    ->prefix('api/v1/tasks')
    ->as('api.v1.tasks.')
    ->group(function () {

        // --- Task Versions ---
        Route::get('tasks/version', [TaskVersionController::class, 'index']);

        // --- Softwares disponíveis para tarefas ---
        Route::get('softwares', [TaskSoftwareController::class, 'index']);

        // --- User Projects ---
        Route::delete('user/project/{project}/dissociate', [UserProjectController::class, 'dissociate'])->name('project.dissociate');
        Route::apiResource('user/project', UserProjectController::class);

        // --- Tasks Genéricas ---
        Route::post('tasks/reference', [TaskController::class, 'reference']);
        Route::post('tasks/dereference', [TaskController::class, 'dereference']);
        Route::get('tasks/report', [TaskController::class, 'report']);

        // --- Tasks Específicas ---
        Route::post('tasks/{task}/seen', [TaskController::class, 'seen'])->name('tasks.seen');
        Route::post('tasks/{task}/upload', [TaskController::class, 'upload'])->name('tasks.upload');
        Route::get('tasks/{task}/attachments', [TaskController::class, 'attachments'])->name('tasks.attachments');
        Route::delete('tasks/{task_id}/attachments/{attachment_id}/remove', [TaskController::class, 'removeAttachment'])->name('tasks.attachments.remove');
        Route::put('tasks/{task_id}/status', [TaskController::class, 'updateStatus']);

        // --- Attachments ---
        Route::get('attachments/{attachment}/download', [TaskController::class, 'downloadAttachment'])->name('attachments.download');

        // --- Labels de Tasks ---
        Route::post('tasks/{task_id}/labels/{label_id}/assign', [LabelController::class, 'assign']);
        Route::post('tasks/{task_id}/labels/{label_id}/dissociate', [LabelController::class, 'dissociate']);

        // --- Notificações (API token) ---
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/{id}/seen', [NotificationController::class, 'seen']);
        Route::post('notifications', [NotificationController::class, 'store']);

        // --- Resources (CRUD) ---
        Route::apiResource('tasks', TaskController::class);
        Route::apiResource('labels', LabelController::class);
        Route::apiResource('comments', CommentController::class);
        Route::apiResource('projects', ProjectController::class);

        // --- Changelogs ---
        Route::get('changelogs/report', [ChangelogController::class, 'report']);
        Route::get('changelogs', [ChangelogController::class, 'index']);
        Route::post('changelogs', [ChangelogController::class, 'store']);
        Route::put('changelogs/{changelog_id}', [ChangelogController::class, 'update']);
        Route::delete('changelogs/{changelog_id}', [ChangelogController::class, 'destroy']);

        // --- Changelog Versions ---
        Route::get('changelogs/versions', [ChangelogVersionController::class, 'index']);
        Route::post('changelogs/versions', [ChangelogVersionController::class, 'store']);
        Route::get('changelogs/versions/{version_id}', [ChangelogVersionController::class, 'show']);
        Route::put('changelogs/versions/{version_id}', [ChangelogVersionController::class, 'update']);
    });
