<?php

namespace App\DTOs;

use App\DTOs\Contracts\DataObject;
use App\Http\Requests\RegisterRequest;

readonly class RegisterData implements DataObject
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $password = null,
    ) {}

    public static function fromRequest(RegisterRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
        ], fn ($value) => $value !== null);
    }
}
