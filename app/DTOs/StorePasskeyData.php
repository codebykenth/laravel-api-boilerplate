<?php

namespace App\DTOs;

use App\DTOs\Contracts\DataObject;
use App\Http\Requests\StorePasskeyRequest;

readonly class StorePasskeyData implements DataObject
{
    /**
     * @param  array<string, mixed>  $credential
     */
    public function __construct(
        public string $name,
        public array $credential,
        public ?string $challengeToken = null,
    ) {}

    public static function fromRequest(StorePasskeyRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            credential: $request->validated('credential'),
            challengeToken: $request->validated('challenge_token'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'credential' => $this->credential,
            'challenge_token' => $this->challengeToken,
        ], fn ($value) => $value !== null);
    }
}
