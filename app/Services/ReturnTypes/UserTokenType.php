<?php
namespace App\Services\ReturnTypes;

enum UserTokenType : int
{
    case NONE = 0;
    case ACCOUNT_ACTIVATION = 1;
    case PASSWORD_RESET = 2;
    case UNLOCK_ACCOUNT = 3;
}
