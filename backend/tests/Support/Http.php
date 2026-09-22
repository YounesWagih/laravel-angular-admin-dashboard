<?php

namespace Tests\Support;

use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class Http
{
    public static function json(
        string $method,
        string $path,
        array $payload = [],
        ?User $user = null,
        array $headers = [],
    ): Response {
        $server = [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ];

        if ($user) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$user->createToken('invariant-suite')->plainTextToken;
        }

        foreach ($headers as $name => $value) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
        }

        $request = Request::create(
            $path,
            strtoupper($method),
            server: $server,
            content: $payload === [] ? null : json_encode($payload, JSON_THROW_ON_ERROR),
        );
        $kernel = app(Kernel::class);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        Auth::forgetGuards();

        return $response;
    }

    public static function decoded(Response $response): array
    {
        return json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }
}
