<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasskeyLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.type' => ['required', 'string', 'in:public-key'],
            'credential.response' => ['required', 'array'],
            'challenge_token' => ['nullable', 'string'],
            'token_name' => ['nullable', 'string'],
        ];
    }
}
