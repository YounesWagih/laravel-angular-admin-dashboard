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
    case WarehousesRead = 'warehouses.read';
    case WarehousesCreate = 'warehouses.create';
    case WarehousesUpdate = 'warehouses.update';
    case WarehousesDelete = 'warehouses.delete';
    case InventoryRead = 'inventory.read';
    case InventoryAdjust = 'inventory.adjust';
    case InventoryTransfer = 'inventory.transfer';
    case OrdersRead = 'orders.read';
    case OrdersUpdate = 'orders.update';
}
