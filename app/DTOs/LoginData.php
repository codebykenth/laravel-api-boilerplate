<?php

namespace App\DTOs;

use App\DTOs\Contracts\DataObject;
use App\Http\Requests\LoginRequest;

readonly class LoginData implements DataObject
{
    public function __construct(
        public ?string $email = null,
        public ?string $password = null,
        public bool $remember = false,
        public ?string $tokenName = null,
    ) {}

    public static function fromRequest(LoginRequest $request): self
    {
        return new self(
            email: $request->validated('email'),
            password: $request->validated('password'),
            remember: (bool) ($request->validated('remember') ?? $request->validated('remember_me') ?? false),
            tokenName: $request->validated('token_name') ?? 'auth_token',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'email' => $this->email,
            'password' => $this->password,
        ], fn ($value) => $value !== null);
    }
}
