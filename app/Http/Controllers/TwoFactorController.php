<?php

namespace App\Http\Controllers;

use App\DTOs\ConfirmTwoFactorData;
use App\Http\Requests\ConfirmTwoFactorRequest;
use App\Services\AuthServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthServiceInterface $service) {}

    public function enable(Request $request): JsonResponse
    {
        $data = $this->service->enableTwoFactor($request->user());

        return $this->successResponse($data, 'Two-factor authentication enabled. Please confirm your setup.');
    }

    public function confirm(ConfirmTwoFactorRequest $request): JsonResponse
    {
        $dto = ConfirmTwoFactorData::fromRequest($request);
        $this->service->confirmTwoFactor($request->user(), $dto);

        return $this->successResponse(null, 'Two-factor authentication confirmed successfully');
    }

    public function disable(Request $request): JsonResponse
    {
        $this->service->disableTwoFactor($request->user());

        return $this->successResponse(null, 'Two-factor authentication disabled successfully');
    }

    public function recoveryCodes(Request $request): JsonResponse
    {
        $codes = $this->service->getTwoFactorRecoveryCodes($request->user());

        return $this->successResponse(['recovery_codes' => $codes], 'Two-factor recovery codes retrieved successfully');
    }

    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $codes = $this->service->generateNewRecoveryCodes($request->user());

        return $this->successResponse(['recovery_codes' => $codes], 'Two-factor recovery codes regenerated successfully');
    }
}
