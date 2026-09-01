<?php

namespace App\Enums;

enum RoleDeletionResult
{
    case Deleted;
    case DefaultRole;
    case AssignedToUsers;
}
