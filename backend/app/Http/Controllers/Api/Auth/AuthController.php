<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\Status;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\AuthenticatedUserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LogicException;

final class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated): User {
            $defaultRole = Role::query()
                ->where('is_default', true)
                ->where('guard_name', 'web')
                ->first();

            if (! $defaultRole) {
                throw new LogicException('A default role must be configured before users can register.');
            }

            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'type' => UserType::User,
                'status' => Status::Active,
            ]);

            $user->syncRoles([$defaultRole]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return AuthenticatedUserResource::make($user->load('roles'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): AuthenticatedUserResource
    {
        $request->authenticate();
        $request->session()->regenerate();

        /** @var User $user */
        $user = $request->user();

        return AuthenticatedUserResource::make($user->load('roles'));
    }

    public function me(Request $request): AuthenticatedUserResource
    {
        /** @var User $user */
        $user = $request->user();

        return AuthenticatedUserResource::make($user->load('roles'));
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
