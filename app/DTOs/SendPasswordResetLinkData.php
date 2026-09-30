<?php

namespace App\DTOs;

use App\DTOs\Contracts\DataObject;
use App\Http\Requests\SendPasswordResetLinkRequest;

readonly class SendPasswordResetLinkData implements DataObject
{
    public function __construct(
        public string $email,
    ) {}

    public static function fromRequest(SendPasswordResetLinkRequest $request): self
    {
        return new self(
            email: $request->validated('email'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'email' => $this->email,
        ], fn ($value) => $value !== null);
    }
}
