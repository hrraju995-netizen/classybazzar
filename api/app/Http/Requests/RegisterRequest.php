<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawIdentifier = trim((string) ($this->input('identifier') ?? $this->input('email') ?? ''));
        $rawPhone = trim((string) ($this->input('phone') ?? ''));

        // If user entered a phone number into the "Email or Phone Number" input
        if ($rawIdentifier !== '' && ! filter_var($rawIdentifier, FILTER_VALIDATE_EMAIL)) {
            $normalizedPhone = $this->normalizePhone($rawIdentifier);
            if ($rawPhone === '') {
                $rawPhone = $normalizedPhone;
            }
            $this->merge([
                'email' => null,
                'phone' => $rawPhone,
            ]);
        } elseif ($rawPhone !== '') {
            $this->merge([
                'phone' => $this->normalizePhone($rawPhone),
            ]);
        }

        if ($this->filled('password') && ! $this->filled('password_confirmation')) {
            $this->merge([
                'password_confirmation' => $this->input('password'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:180', 'unique:users,email'],
            'phone' => ['nullable', 'required_without:email', 'string', 'regex:/^01[3-9]\d{8}$/', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(6)],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required_without' => 'Please enter your email address or Bangladeshi mobile number.',
            'phone.required_without' => 'Please enter your mobile number or email address.',
            'phone.regex' => 'Enter a valid 11-digit Bangladeshi mobile number (e.g. 01712345678).',
            'phone.unique' => 'This phone number is already registered. Please log in instead.',
            'email.unique' => 'This email address is already registered. Please log in instead.',
        ];
    }

    protected function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value);

        return preg_replace('/^88(?=01)/', '', (string) $digits);
    }
}
