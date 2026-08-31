<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\IndexUsersRequest;
use App\Http\Requests\Api\User\StoreUserRequest;
use App\Http\Requests\Api\User\UpdateUserRequest;
use App\Http\Requests\Api\User\UpdateUserStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

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
        $user = $this->userService->create($request->validated());

        return UserResource::make($user)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource|JsonResponse
    {
        $updatedUser = $this->userService->update($user, $request->validated());

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

        $updatedUser = $this->userService->updateStatus($user, $status);

        if (! $updatedUser) {
            return response()->json([
                'message' => 'The final active administrator cannot be deactivated.',
            ], Response::HTTP_CONFLICT);
        }

        return UserResource::make($updatedUser->load('roles'));
    }
}
