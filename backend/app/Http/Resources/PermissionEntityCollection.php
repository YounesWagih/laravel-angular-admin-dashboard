<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class PermissionEntityCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        $entities = [];
        $actionOrder = array_flip(['create', 'read', 'update', 'delete']);

        foreach ($this->collection as $permission) {
            [$entity, $action] = $this->permissionParts($permission->name);

            if ($entity === '' || $action === '') {
                continue;
            }

            $entities[$entity][] = [
                'name' => $permission->name,
                'action' => $action,
            ];
        }

        ksort($entities);

        return array_values(array_map(
            static function (array $permissions, string $entity) use ($actionOrder): array {
                usort(
                    $permissions,
                    static fn (array $left, array $right): int => ($actionOrder[$left['action']] ?? PHP_INT_MAX)
                        <=> ($actionOrder[$right['action']] ?? PHP_INT_MAX),
                );

                return [
                    'name' => $entity,
                    'permissions' => $permissions,
                ];
            },
            $entities,
            array_keys($entities),
        ));
    }

    private function permissionParts(string $permission): array
    {
        if (! str_contains($permission, '.')) {
            return ['', ''];
        }

        return explode('.', $permission, 2);
    }
}
