<?php

namespace App\Services;

use App\Enums\SocialProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Override;

class TokenAuthService extends AuthService
{
    #[Override]
    public function authenticated(User $user, ?string $tokenName = 'auth_token'): array
    {
        $token = $user->createToken($tokenName ?? 'auth_token');

        return [
            'user' => $user,
            'token' => $token->plainTextToken,
        ];
    }

    #[Override]
    public function logout(Request $request): void
    {
        $request->user()?->currentAccessToken()?->delete();
    }

    #[Override]
    protected function getSocialiteDriver(SocialProvider $provider): Provider
    {
        return Socialite::driver($provider->value)->stateless();
    }
}
