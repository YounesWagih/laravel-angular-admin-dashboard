<?php

namespace App\Http\Controllers\Api\Role;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Role\StoreRoleRequest;
use App\Http\Requests\Api\Role\SyncRolePermissionsRequest;
use App\Http\Requests\Api\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roleService) {}

    public function index(): AnonymousResourceCollection
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->withCount('users')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        return RoleResource::collection($roles)->additional([
            'meta' => [
                'permission_entities' => $this->permissionEntities($permissions),
            ],
        ]);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roleService->create($request->validated());

        return RoleResource::make($role)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $role = $this->roleService->update($role, $request->validated());

        return RoleResource::make($role);
    }

    public function setDefault(Role $role): RoleResource
    {
        $role = $this->roleService->setDefault($role);

        return RoleResource::make($role);
    }

    public function destroy(Role $role): Response|JsonResponse
    {
        if ($role->is_default) {
            return response()->json([
                'message' => 'The default role cannot be deleted.',
            ], Response::HTTP_CONFLICT);
        }

        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'A role assigned to users cannot be deleted.',
            ], Response::HTTP_CONFLICT);
        }

        $role->delete();

        return response()->noContent();
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): RoleResource
    {
        $role = $this->roleService->syncPermissions(
            $role,
            $request->validated('permissions'),
        );

        return RoleResource::make($role);
    }

    public function availableEntities(Role $role): JsonResponse
    {
        $assignedEntities = $role->permissions()
            ->pluck('name')
            ->map(fn (string $permission): string => $this->permissionEntity($permission))
            ->filter()
            ->unique();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->reject(
                fn (Permission $permission): bool => $assignedEntities->contains(
                    $this->permissionEntity($permission->name),
                ),
            );

        return response()->json([
            'data' => $this->permissionEntities($permissions),
        ]);
    }

    private function permissionEntities(iterable $permissions): array
    {
        $entities = [];
        $actionOrder = array_flip(['create', 'read', 'update', 'delete']);

        foreach ($permissions as $permission) {
            $entity = $this->permissionEntity($permission->name);
            $action = str_contains($permission->name, '.')
                ? explode('.', $permission->name, 2)[1]
                : '';

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

    private function permissionEntity(string $permission): string
    {
        return str_contains($permission, '.')
            ? explode('.', $permission, 2)[0]
            : '';
    }
}
