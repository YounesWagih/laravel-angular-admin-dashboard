<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserAuthenticationRepository;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Validation\ValidationException;

final class AuthenticationService
{
    private const string TOKEN_NAME = 'angular-web';

    private const int STANDARD_TOKEN_HOURS = 8;

    private const int REMEMBERED_TOKEN_DAYS = 30;

    public function __construct(
        private readonly UserAuthenticationRepository $authentication,
        private readonly UserRepository $users,
    ) {}

    public function authenticate(array $credentials): array
    {
        $user = $this->authentication->authenticate(
            $credentials['email'],
            $credentials['password'],
        );

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => __('The provided credentials are incorrect.'),
            ]);
        }

        return $this->issueToken(
            $this->users->withAuthenticationContext($user),
            $credentials['remember'] ?? false,
        );
    }

    public function issueToken(User $user, bool $remember = false): array
    {
        $expiresAt = $remember
            ? now()->addDays(self::REMEMBERED_TOKEN_DAYS)
            : now()->addHours(self::STANDARD_TOKEN_HOURS);

        return [
            'user' => $user,
            'access_token' => $this->authentication->createToken(
                $user,
                self::TOKEN_NAME,
                $expiresAt,
            ),
            'expires_at' => $expiresAt,
        ];
    }

    public function logout(User $user): void
    {
        $this->authentication->revokeCurrentToken($user);
    }
}
