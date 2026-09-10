<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\AuthenticatedSessionResource;
use App\Http\Resources\AuthenticatedUserResource;
use App\Models\User;
use App\Services\AuthenticationService;
use App\Services\RegistrationService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly RegistrationService $registrationService,
        private readonly UserService $userService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->registrationService->register($request->validated());
        $session = $this->authenticationService->issueToken($user);

        return AuthenticatedSessionResource::make($session)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): AuthenticatedSessionResource
    {
        $session = $this->authenticationService->authenticate($request->validated());

        return AuthenticatedSessionResource::make($session);
    }

    public function me(Request $request): AuthenticatedUserResource
    {
        $user = $this->userService->authenticationDetails($request->user());

        return AuthenticatedUserResource::make($user);
    }

    public function logout(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $this->authenticationService->logout($user);

        return response()->noContent();
    }
}
