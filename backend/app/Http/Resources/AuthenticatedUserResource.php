<?php

namespace App\Http\Resources;

use App\Enums\PermissionName;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class AuthenticatedUserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $isAdmin = $this->type === UserType::Admin;
        $role = $this->roles->first();

        $permissions = $isAdmin
            ? array_map(
                static fn (PermissionName $permission): string => $permission->value,
                PermissionName::cases(),
            )
            : $this->getAllPermissions()->pluck('name')->sort()->values()->all();

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
