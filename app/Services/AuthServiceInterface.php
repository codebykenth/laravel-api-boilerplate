<?php

namespace App\Services;

use App\DTOs\ConfirmTwoFactorData;
use App\DTOs\LoginData;
use App\DTOs\PasskeyLoginData;
use App\DTOs\RegisterData;
use App\DTOs\ResetPasswordData;
use App\DTOs\SendPasswordResetLinkData;
use App\DTOs\SocialProviderData;
use App\DTOs\StorePasskeyData;
use App\DTOs\TwoFactorChallengeData;
use App\DTOs\VerifyEmailData;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Passkeys\Passkey;

interface AuthServiceInterface
{
    public function register(RegisterData $data): array;

    public function login(LoginData $data): array;

    public function authenticated(User $user, ?string $tokenName = 'auth_token'): array;

    public function logout(Request $request): void;

    public function sendPasswordResetLink(SendPasswordResetLinkData $data): string;

    public function resetPassword(ResetPasswordData $data): string;

    public function verifyEmail(VerifyEmailData $data): array;

    public function resendVerificationEmail(User $user): string;

    public function redirectToProvider(SocialProviderData $data): string;

    public function handleProviderCallback(SocialProviderData $data): array;

    public function twoFactorChallenge(TwoFactorChallengeData $data): array;

    public function enableTwoFactor(User $user): array;

    public function confirmTwoFactor(User $user, ConfirmTwoFactorData $data): void;

    public function disableTwoFactor(User $user): void;

    public function getTwoFactorRecoveryCodes(User $user): array;

    public function generateNewRecoveryCodes(User $user): array;

    public function getPasskeyLoginOptions(): array;

    public function passkeyLogin(PasskeyLoginData $data): array;

    public function getPasskeyRegistrationOptions(User $user): array;

    public function storePasskey(User $user, StorePasskeyData $data): Passkey;

    public function deletePasskey(User $user, Passkey $passkey): void;
}
