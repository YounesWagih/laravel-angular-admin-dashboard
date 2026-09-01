<?php

namespace App\Http\Controllers\Api\Role;

use App\Enums\RoleDeletionResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Role\StoreRoleRequest;
use App\Http\Requests\Api\Role\SyncRolePermissionsRequest;
use App\Http\Requests\Api\Role\UpdateRoleRequest;
use App\Http\Resources\PermissionEntityCollection;
use App\Http\Resources\RoleResource;
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
        $roles = $this->roleService->getAll();
        $permissions = $this->roleService->getAllPermissions();

        return RoleResource::collection($roles)->additional([
            'meta' => [
                'permission_entities' => PermissionEntityCollection::make($permissions)->resolve(),
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
        return match ($this->roleService->delete($role)) {
            RoleDeletionResult::Deleted => response()->noContent(),
            RoleDeletionResult::DefaultRole => response()->json([
                'message' => 'The default role cannot be deleted.',
            ], Response::HTTP_CONFLICT),
            RoleDeletionResult::AssignedToUsers => response()->json([
                'message' => 'A role assigned to users cannot be deleted.',
            ], Response::HTTP_CONFLICT),
        };
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): RoleResource
    {
        $role = $this->roleService->syncPermissions(
            $role,
            $request->validated('permissions'),
        );

        return RoleResource::make($role);
    }

    public function availableEntities(Role $role): PermissionEntityCollection
    {
        return PermissionEntityCollection::make(
            $this->roleService->availablePermissions($role),
        );
    }
}
