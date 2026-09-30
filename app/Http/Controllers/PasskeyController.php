<?php

namespace App\Http\Controllers;

use App\DTOs\PasskeyLoginData;
use App\DTOs\StorePasskeyData;
use App\Http\Requests\PasskeyLoginRequest;
use App\Http\Requests\StorePasskeyRequest;
use App\Http\Resources\PasskeyResource;
use App\Services\AuthServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Passkey;

class PasskeyController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthServiceInterface $service) {}

    public function loginOptions(): JsonResponse
    {
        $options = $this->service->getPasskeyLoginOptions();

        return $this->successResponse($options, 'Passkey login options generated successfully');
    }

    public function login(PasskeyLoginRequest $request): JsonResponse
    {
        $dto = PasskeyLoginData::fromRequest($request);
        $response = $this->service->passkeyLogin($dto);

        return $this->successResponse($response, 'User logged in successfully');
    }

    public function registrationOptions(Request $request): JsonResponse
    {
        $options = $this->service->getPasskeyRegistrationOptions($request->user());

        return $this->successResponse($options, 'Passkey registration options generated successfully');
    }

    public function store(StorePasskeyRequest $request): JsonResponse
    {
        $dto = StorePasskeyData::fromRequest($request);
        $passkey = $this->service->storePasskey($request->user(), $dto);

        return $this->successResponse(
            new PasskeyResource($passkey),
            'Passkey registered successfully',
            201
        );
    }

    public function index(Request $request): JsonResponse
    {
        $passkeys = $request->user()->passkeys()->latest()->get();

        return $this->successResponse(
            PasskeyResource::collection($passkeys),
            'Passkeys retrieved successfully'
        );
    }

    public function destroy(Request $request, Passkey $passkey): JsonResponse
    {
        $this->service->deletePasskey($request->user(), $passkey);

        return $this->successResponse(null, 'Passkey deleted successfully');
    }
}
