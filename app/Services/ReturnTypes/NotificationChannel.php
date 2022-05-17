<?php
namespace App\Services\ReturnTypes;

enum NotificationChannel : string
{
    case NONE = '';
    case EMAIL = 'email';
    case SLACK = 'slack';
    case DISCORD = 'discord';
}
