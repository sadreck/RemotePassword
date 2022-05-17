<?php
namespace App\Services\ReturnTypes;

enum PasswordResult : int
{
    case NONE = 0;
    case SUCCESS = 1;
    case DISABLED = 2;
    case RESTRICTION_FAILED_IP = 3;
    case RESTRICTION_FAILED_DATE = 4;
    case RESTRICTION_FAILED_TIME = 5;
    case RESTRICTION_FAILED_DAY = 6;
    case RESTRICTION_FAILED_USERAGENT = 7;
    case RESTRICTION_FAILED_MAXUSES = 8;
}
