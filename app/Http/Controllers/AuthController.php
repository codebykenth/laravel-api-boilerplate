<?php

namespace App\Http\Controllers;

use App\DTOs\LoginData;
use App\DTOs\RegisterData;
use App\DTOs\ResetPasswordData;
use App\DTOs\SendPasswordResetLinkData;
use App\DTOs\SocialProviderData;
use App\DTOs\TwoFactorChallengeData;
use App\DTOs\VerifyEmailData;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SendPasswordResetLinkRequest;
use App\Http\Requests\SocialProviderRequest;
use App\Http\Requests\TwoFactorChallengeRequest;
use App\Http\Requests\VerifiyEmailRequest;
use App\Services\AuthServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthServiceInterface $service) {}

    public function login(LoginRequest $request)
    {
        $dto = LoginData::fromRequest($request);
        $response = $this->service->login($dto);

        return $this->successResponse($response, 'User logged in successfully');
    }

    public function register(RegisterRequest $request)
    {
        $dto = RegisterData::fromRequest($request);
        $response = $this->service->register($dto);

        return $this->successResponse($response, 'User registered successfully');
    }

    public function logout(Request $request)
    {
        $this->service->logout($request);

        return $this->successResponse([], 'User logged out successfully');
    }

    public function sendPasswordResetLink(SendPasswordResetLinkRequest $request)
    {
        $dto = SendPasswordResetLinkData::fromRequest($request);
        $message = $this->service->sendPasswordResetLink($dto);

        return $this->successResponse(null, $message);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $dto = ResetPasswordData::fromRequest($request);
        $message = $this->service->resetPassword($dto);

        return $this->successResponse(null, $message);
    }

    public function showResetPasswordForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function verifyEmail(VerifiyEmailRequest $request)
    {
        $dto = VerifyEmailData::fromRequest($request);
        $result = $this->service->verifyEmail($dto);

        return view('auth.verify-email', [
            'status' => $result['status'],
            'user' => $result['user'],
        ]);
    }

    public function resendVerificationEmail(Request $request)
    {
        $message = $this->service->resendVerificationEmail($request->user());

        return $this->successResponse(null, $message);
    }

    /**
     * * Get provider link where user can login
     */
    public function redirectToProvider(SocialProviderRequest $request)
    {
        $dto = SocialProviderData::fromRequest($request);
        $targetUrl = $this->service->redirectToProvider($dto);

        return $this->successResponse(['url' => $targetUrl], 'Redirect URL generated successfully');
    }

    public function handleProviderCallback(SocialProviderRequest $request)
    {
        $dto = SocialProviderData::fromRequest($request);
        $response = $this->service->handleProviderCallback($dto);

        return $this->successResponse($response, 'User logged in successfully');
    }

    public function twoFactorChallenge(TwoFactorChallengeRequest $request)
    {
        $dto = TwoFactorChallengeData::fromRequest($request);
        $response = $this->service->twoFactorChallenge($dto);

        return $this->successResponse($response, 'User logged in successfully');
    }
}
