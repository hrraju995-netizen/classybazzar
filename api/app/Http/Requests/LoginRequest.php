<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Accepts either an email address or a phone number.
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}
