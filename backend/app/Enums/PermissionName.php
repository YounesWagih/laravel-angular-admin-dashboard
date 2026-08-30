<?php

namespace App\Enums;

enum PermissionName: string
{
    case ProductsRead = 'products.read';
    case ProductsCreate = 'products.create';
    case ProductsUpdate = 'products.update';
    case ProductsDelete = 'products.delete';
    case CategoriesRead = 'categories.read';
    case CategoriesCreate = 'categories.create';
    case CategoriesUpdate = 'categories.update';
    case CategoriesDelete = 'categories.delete';
}
