<?php
namespace App\Services\ReturnTypes;

enum PasswordNotificationType : string
{
    case NONE = '';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';
}
