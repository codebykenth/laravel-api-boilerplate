<?php

namespace App\DTOs;

use App\DTOs\Contracts\DataObject;
use App\Http\Requests\TwoFactorChallengeRequest;

readonly class TwoFactorChallengeData implements DataObject
{
    public function __construct(
        public string $challengeToken,
        public ?string $code = null,
        public ?string $recoveryCode = null,
    ) {}

    public static function fromRequest(TwoFactorChallengeRequest $request): self
    {
        return new self(
            challengeToken: $request->validated('challenge_token'),
            code: $request->validated('code'),
            recoveryCode: $request->validated('recovery_code'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'challenge_token' => $this->challengeToken,
            'code' => $this->code,
            'recovery_code' => $this->recoveryCode,
        ], fn ($value) => $value !== null);
    }
}
