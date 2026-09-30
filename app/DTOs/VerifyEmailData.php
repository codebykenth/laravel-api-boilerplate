<?php

namespace App\DTOs;

use App\DTOs\Contracts\DataObject;
use App\Http\Requests\VerifiyEmailRequest;

readonly class VerifyEmailData implements DataObject
{
    public function __construct(
        public string $id,
        public string $hash,
    ) {}

    public static function fromRequest(VerifiyEmailRequest $request): self
    {
        return new self(
            id: (string) ($request->validated('id') ?? $request->route('id')),
            hash: (string) ($request->validated('hash') ?? $request->route('hash')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'hash' => $this->hash,
        ];
    }
}
