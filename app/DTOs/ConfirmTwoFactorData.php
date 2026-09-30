<?php

namespace App\DTOs;

use App\DTOs\Contracts\DataObject;
use App\Http\Requests\ConfirmTwoFactorRequest;

readonly class ConfirmTwoFactorData implements DataObject
{
    public function __construct(
        public string $code,
    ) {}

    public static function fromRequest(ConfirmTwoFactorRequest $request): self
    {
        return new self(
            code: $request->validated('code'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
        ];
    }
}
