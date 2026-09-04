<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal privado de notificações de tasks por usuário
Broadcast::channel('tasks.user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
