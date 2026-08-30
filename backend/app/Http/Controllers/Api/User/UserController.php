<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\Status;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\IndexUsersRequest;
use App\Http\Requests\Api\User\StoreUserRequest;
use App\Http\Requests\Api\User\UpdateUserRequest;
use App\Http\Requests\Api\User\UpdateUserStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

final class UserController extends Controller
{
    public function index(IndexUsersRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $users = User::query()
            ->with('roles')
            ->when($validated['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(
                $validated['type'] ?? null,
                fn (Builder $query, string $type): Builder => $query->where('type', $type),
            )
            ->when(
                $validated['role_id'] ?? null,
                fn (Builder $query, int $roleId): Builder => $query->whereHas(
                    'roles',
                    fn (Builder $query): Builder => $query->whereKey($roleId),
                ),
            )
            ->when(
                $validated['status'] ?? null,
                fn (Builder $query, string $status): Builder => $query->where('status', $status),
            )
            ->orderBy('name')
            ->paginate($validated['per_page'] ?? 10)
            ->withQueryString();

        return UserResource::collection($users);
    }

    public function show(User $user): UserResource
    {
        return UserResource::make($user->load('roles'));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $role = $this->role($validated['role_id']);
        unset($validated['role_id']);

        $user = DB::transaction(function () use ($validated, $role): User {
            $user = User::query()->create($validated);
            $user->syncRoles([$role]);

            return $user;
        });

        return UserResource::make($user->load('roles'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource|JsonResponse
    {
        $validated = $request->validated();
        $role = isset($validated['role_id']) ? $this->role($validated['role_id']) : null;
        unset($validated['role_id']);

        $updatedUser = DB::transaction(function () use ($user, $validated, $role): ?User {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $nextType = isset($validated['type'])
                ? UserType::from($validated['type'])
                : $user->type;

            if ($user->type === UserType::Admin
                && $user->status === Status::Active
                && $nextType !== UserType::Admin
                && $this->isFinalActiveAdmin()) {
                return null;
            }

            $user->update($validated);

            if ($role) {
                $user->syncRoles([$role]);
            }

            return $user;
        });

        if (! $updatedUser) {
            return response()->json([
                'message' => 'The final active administrator cannot be changed to a normal user.',
            ], Response::HTTP_CONFLICT);
        }

        return UserResource::make($updatedUser->load('roles'));
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): UserResource|JsonResponse
    {
        $status = Status::from($request->validated('status'));

        if ($request->user()->is($user) && $status === Status::Inactive) {
            return response()->json([
                'message' => 'You cannot deactivate your own account.',
            ], Response::HTTP_CONFLICT);
        }

        $updatedUser = DB::transaction(function () use ($user, $status): ?User {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($user->status === $status) {
                return $user;
            }

            if ($user->type === UserType::Admin
                && $user->status === Status::Active
                && $status === Status::Inactive
                && $this->isFinalActiveAdmin()) {
                return null;
            }

            $user->update(['status' => $status]);

            if ($status === Status::Inactive) {
                $this->invalidateAuthentication($user);
            }

            return $user;
        });

        if (! $updatedUser) {
            return response()->json([
                'message' => 'The final active administrator cannot be deactivated.',
            ], Response::HTTP_CONFLICT);
        }

        return UserResource::make($updatedUser->load('roles'));
    }

    private function role(int $roleId): Role
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->findOrFail($roleId);
    }

    private function isFinalActiveAdmin(): bool
    {
        return User::query()
            ->where('type', UserType::Admin)
            ->where('status', Status::Active)
            ->lockForUpdate()
            ->count() === 1;
    }

    private function invalidateAuthentication(User $user): void
    {
        DB::connection(config('session.connection'))
            ->table(config('session.table'))
            ->where('user_id', $user->id)
            ->delete();

        $user->tokens()->delete();
    }
}
