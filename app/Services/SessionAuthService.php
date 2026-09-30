<?php

namespace App\Services;

use App\Enums\SocialProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Override;

class SessionAuthService extends AuthService
{
    #[Override]
    public function authenticated(User $user, ?string $tokenName = 'auth_token'): array
    {
        Auth::login($user);
        request()->session()->regenerate();

        return [
            'user' => $user,
        ];
    }

    #[Override]
    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    #[Override]
    protected function getSocialiteDriver(SocialProvider $provider): Provider
    {
        return Socialite::driver($provider->value);
    }
}
