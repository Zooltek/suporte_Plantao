<?php

namespace App\Enums;

enum FeedbackStatus: string
{
    case PENDING = 'pen';
    case CANCELED = 'can';
    case FINISHED = 'fin';
}