<?php

namespace App\DTOs;

use App\DTOs\Contracts\DataObject;
use App\Enums\SocialProvider;
use App\Http\Requests\SocialProviderRequest;

readonly class SocialProviderData implements DataObject
{
    public function __construct(
        public SocialProvider $provider,
    ) {}

    public static function fromRequest(SocialProviderRequest $request): self
    {
        return new self(
            SocialProvider::from($request->validated('provider'))
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'provider' => $this->provider,
        ], fn ($value) => $value !== null);
    }
}
