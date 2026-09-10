<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin array{user: User, access_token: string, expires_at: Carbon} */
final class AuthenticatedSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user' => AuthenticatedUserResource::make($this->resource['user']),
            'access_token' => $this->resource['access_token'],
            'token_type' => 'Bearer',
            'expires_at' => $this->resource['expires_at']->toAtomString(),
        ];
    }
}
