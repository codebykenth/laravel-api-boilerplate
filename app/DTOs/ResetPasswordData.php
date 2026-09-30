<?php

namespace App\DTOs;

use App\DTOs\Contracts\DataObject;
use App\Http\Requests\ResetPasswordRequest;

readonly class ResetPasswordData implements DataObject
{
    public function __construct(
        public ?string $token = null,
        public ?string $email = null,
        public ?string $password = null,
        public ?string $passwordConfirmation = null,
    ) {}

    public static function fromRequest(ResetPasswordRequest $request): self
    {
        return new self(
            token: $request->route('token') ?? $request->validated('token'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            passwordConfirmation: $request->validated('password_confirmation'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'token' => $this->token,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->passwordConfirmation,
        ], fn ($value) => $value !== null);
    }
}
