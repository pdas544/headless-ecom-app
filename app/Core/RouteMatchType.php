<?php

declare(strict_types=1);

namespace App\Core;

enum RouteMatchType
{
    case Found;
    case NotFound;
    case MethodNotAllowed;
}
