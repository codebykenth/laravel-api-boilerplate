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
use App\Enums\SocialProvider;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider as TwoFactorAuthenticationProviderContract;
use Laravel\Passkeys\Actions\DeletePasskey;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Support\WebAuthn;
use Laravel\Socialite\Contracts\Provider;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

abstract class AuthService implements AuthServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $repository) {}

    abstract protected function getSocialiteDriver(SocialProvider $provider): Provider;

    public function register(RegisterData $data): array
    {
        $user = User::firstOrCreate($data->toArray());
        event(new Registered($user));
        Auth::login($user);

        return [
            'user' => $user,
        ];
    }

    public function login(LoginData $data): array
    {
        $credentials = [
            'email' => $data->email,
            'password' => $data->password,
        ];
        if (! Auth::attempt($credentials, $data->remember)) {
            throw new AuthenticationException('Invalid credentials. Please try again.');
        }

        $user = Auth::user();

        if (! $user->hasVerifiedEmail()) {
            throw new AuthenticationException('Please verify your email first.');
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $challengeToken = Str::random(64);
            cache()->put('2fa_challenge:'.$challengeToken, [
                'user_id' => $user->id,
                'remember' => $data->remember,
                'token_name' => $data->tokenName,
            ], now()->addMinutes(5));

            return [
                'two_factor' => true,
                'challenge_token' => $challengeToken,
            ];
        }

        return $this->authenticated($user, $data->tokenName);
    }

    public function sendPasswordResetLink(SendPasswordResetLinkData $data): string
    {
        $status = Password::sendResetLink($data->toArray());

        if ($status === Password::RESET_THROTTLED) {
            throw new TooManyRequestsHttpException(message: __($status));
        }

        // Return a generic response to prevent user enumeration
        return __('passwords.sent');
    }

    public function resetPassword(ResetPasswordData $data): string
    {
        $status = Password::reset(
            $data->toArray(),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                $user->tokens()->delete();
                DB::table('sessions')->where('user_id', $user->id)->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }

    public function verifyEmail(VerifyEmailData $data): array
    {
        /** @var User $user */
        $user = $this->repository->findOrFail($data->id);

        if (! hash_equals((string) $data->hash, sha1($user->getEmailForVerification()))) {
            throw new AuthorizationException('Invalid verification link.');
        }

        $status = 'already_verified';
        $message = 'Email already verified';

        if (! $user->hasVerifiedEmail()) {
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }

            $status = 'verified';
            $message = 'Email verified successfully';
        }

        Auth::login($user);
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'status' => $status,
            'message' => $message,
            'user' => $user,
            'token' => $token,
        ];
    }

    public function resendVerificationEmail(User $user): string
    {
        if ($user->hasVerifiedEmail()) {
            return 'Email already verified';
        }

        $user->sendEmailVerificationNotification();

        return 'Email verification link sent successfully';
    }

    public function redirectToProvider(SocialProviderData $data): string
    {
        return $this->getSocialiteDriver($data->provider)->redirect()->getTargetUrl();
    }

    public function handleProviderCallback(SocialProviderData $data): array
    {
        $socialUser = $this->getSocialiteDriver($data->provider)->user();

        $user = User::updateOrCreate(
            ['email' => $socialUser->getEmail()],
            [
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                'provider_id' => $socialUser->getId(),
                'provider' => $data->provider,
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(32)),
            ]
        );

        if ($user->wasRecentlyCreated) {
            event(new Registered($user));
        }

        return $this->authenticated($user);
    }

    public function twoFactorChallenge(TwoFactorChallengeData $data): array
    {
        $payload = cache()->get('2fa_challenge:'.$data->challengeToken);

        if (! $payload) {
            throw ValidationException::withMessages([
                'challenge_token' => ['Two factor challenge expired or invalid.'],
            ]);
        }

        /** @var User $user */
        $user = $this->repository->findOrFail($payload['user_id']);

        if ($data->code) {
            $provider = app(TwoFactorAuthenticationProviderContract::class);
            if (! $provider->verify(decrypt($user->two_factor_secret), $data->code)) {
                throw ValidationException::withMessages([
                    'code' => ['The provided two factor authentication code was invalid.'],
                ]);
            }
        } elseif ($data->recoveryCode) {
            $valid = collect($user->recoveryCodes())->first(fn ($code) => hash_equals($code, $data->recoveryCode));
            if (! $valid) {
                throw ValidationException::withMessages([
                    'recovery_code' => ['The provided two factor recovery code was invalid.'],
                ]);
            }
            $user->replaceRecoveryCode($data->recoveryCode);
        } else {
            throw ValidationException::withMessages([
                'code' => ['Please provide a two factor authentication code or recovery code.'],
            ]);
        }

        cache()->forget('2fa_challenge:'.$data->challengeToken);

        return $this->authenticated($user, $payload['token_name'] ?? 'auth_token');
    }

    public function enableTwoFactor(User $user): array
    {
        app(EnableTwoFactorAuthentication::class)($user);

        return [
            'svg' => $user->twoFactorQrCodeSvg(),
            'secret_key' => decrypt($user->two_factor_secret),
            'recovery_codes' => $user->recoveryCodes(),
        ];
    }

    public function confirmTwoFactor(User $user, ConfirmTwoFactorData $data): void
    {
        app(ConfirmTwoFactorAuthentication::class)($user, $data->code);
    }

    public function disableTwoFactor(User $user): void
    {
        app(DisableTwoFactorAuthentication::class)($user);
    }

    public function getTwoFactorRecoveryCodes(User $user): array
    {
        return $user->recoveryCodes();
    }

    public function generateNewRecoveryCodes(User $user): array
    {
        app(GenerateNewRecoveryCodes::class)($user);

        return $user->recoveryCodes();
    }

    public function getPasskeyLoginOptions(): array
    {
        $options = app(GenerateVerificationOptions::class)();
        $serialized = WebAuthn::toJson($options);
        $challengeToken = Str::random(64);

        cache()->put('passkey.verification_options:'.$challengeToken, $serialized, now()->addMinutes(5));
        session()->put('passkey.verification_options', $serialized);

        return [
            'options' => WebAuthn::toBrowserArray($options),
            'challenge_token' => $challengeToken,
        ];
    }

    public function passkeyLogin(PasskeyLoginData $data): array
    {
        $serialized = null;
        if ($data->challengeToken) {
            $serialized = cache()->pull('passkey.verification_options:'.$data->challengeToken);
        }
        if (! $serialized && session()->has('passkey.verification_options')) {
            $serialized = session()->pull('passkey.verification_options');
        }

        if (! $serialized) {
            throw ValidationException::withMessages([
                'credential' => ['Passkey verification session expired. Please try again.'],
            ]);
        }

        try {
            $publicKeyCredential = WebAuthn::fromJson(
                json_encode($data->credential) ?: '{}',
                PublicKeyCredential::class
            );
            $verificationOptions = WebAuthn::fromJson($serialized, PublicKeyCredentialRequestOptions::class);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'credential' => ['Invalid credential format.'],
            ]);
        }

        $passkey = app(VerifyPasskey::class)($publicKeyCredential, $verificationOptions);

        return $this->authenticated($passkey->user, $data->tokenName);
    }

    public function getPasskeyRegistrationOptions(User $user): array
    {
        $options = app(GenerateRegistrationOptions::class)($user);
        $serialized = WebAuthn::toJson($options);
        $challengeToken = Str::random(64);

        cache()->put('passkey.registration_options:'.$challengeToken, $serialized, now()->addMinutes(5));
        session()->put('passkey.registration_options', $serialized);

        return [
            'options' => WebAuthn::toBrowserArray($options),
            'challenge_token' => $challengeToken,
        ];
    }

    public function storePasskey(User $user, StorePasskeyData $data): Passkey
    {
        $serialized = null;
        if ($data->challengeToken) {
            $serialized = cache()->pull('passkey.registration_options:'.$data->challengeToken);
        }
        if (! $serialized && session()->has('passkey.registration_options')) {
            $serialized = session()->pull('passkey.registration_options');
        }

        if (! $serialized) {
            throw ValidationException::withMessages([
                'credential' => ['Passkey registration session expired. Please try again.'],
            ]);
        }

        try {
            $publicKeyCredential = WebAuthn::fromJson(
                json_encode($data->credential) ?: '{}',
                PublicKeyCredential::class
            );
            $registrationOptions = WebAuthn::fromJson($serialized, PublicKeyCredentialCreationOptions::class);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'credential' => ['Invalid credential format.'],
            ]);
        }

        return app(StorePasskey::class)($user, $data->name, $publicKeyCredential, $registrationOptions);
    }

    public function deletePasskey(User $user, Passkey $passkey): void
    {
        if ($passkey->user_id !== $user->id) {
            throw new AuthorizationException('You do not own this passkey.');
        }

        app(DeletePasskey::class)($user, $passkey);
    }
}
