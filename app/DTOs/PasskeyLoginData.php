<?php

namespace App\DTOs;

use App\DTOs\Contracts\DataObject;
use App\Http\Requests\PasskeyLoginRequest;

readonly class PasskeyLoginData implements DataObject
{
    /**
     * @param  array<string, mixed>  $credential
     */
    public function __construct(
        public array $credential,
        public ?string $challengeToken = null,
        public ?string $tokenName = 'auth_token',
    ) {}

    public static function fromRequest(PasskeyLoginRequest $request): self
    {
        return new self(
            credential: $request->validated('credential'),
            challengeToken: $request->validated('challenge_token'),
            tokenName: $request->validated('token_name') ?? 'auth_token',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'credential' => $this->credential,
            'challenge_token' => $this->challengeToken,
            'token_name' => $this->tokenName,
        ], fn ($value) => $value !== null);
    }
}
