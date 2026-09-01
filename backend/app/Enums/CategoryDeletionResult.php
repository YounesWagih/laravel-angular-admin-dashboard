<?php

namespace App\Enums;

enum CategoryDeletionResult
{
    case Deleted;
    case HasProducts;
}
