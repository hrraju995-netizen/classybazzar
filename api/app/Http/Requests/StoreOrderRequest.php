<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:180'],
            'address' => ['required', 'string', 'max:500'],
            'shipping_area' => ['required', Rule::in(['inside', 'outside'])],
            'payment_method' => ['nullable', Rule::in(['cod', 'bkash', 'nagad', 'sslcommerz'])],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'note' => ['nullable', 'string', 'max:1000'],

            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid Bangladeshi mobile number (e.g. 01712345678).',
            'items.required' => 'Your cart is empty.',
        ];
    }
}
