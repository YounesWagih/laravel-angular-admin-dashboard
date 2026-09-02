<?php

namespace App\Http\Resources;

use App\Enums\PermissionName;
use App\Enums\UserType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AuthenticatedUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAdmin = $this->type === UserType::Admin;
        $role = $this->roles->first();

        $permissions = $isAdmin
            ? array_map(
                static fn (PermissionName $permission): string => $permission->value,
                PermissionName::cases(),
            )
            : $this->effectivePermissions->pluck('name')->sort()->values()->all();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'is_admin' => $isAdmin,
            'role' => $role ? [
                'id' => $role->id,
                'name' => $role->name,
            ] : null,
            'permissions' => $permissions,
        ];
    }
}
