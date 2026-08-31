<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\AuthenticatedUserResource;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

final class AuthController extends Controller
{
    public function __construct(private readonly RegistrationService $registrationService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->registrationService->register($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return AuthenticatedUserResource::make($user)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): AuthenticatedUserResource
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();

        return AuthenticatedUserResource::make($user->load('roles'));
    }

    public function me(Request $request): AuthenticatedUserResource
    {
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
