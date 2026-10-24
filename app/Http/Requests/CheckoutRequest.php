<?php

namespace App\Http\Requests;

use App\Rules\Turnstile;
use App\Support\PakistaniPhone;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // guest checkout – no accounts
    }

    protected function prepareForValidation(): void
    {
        // Trim and strip HTML from free-text fields before validating
        $clean = fn ($value) => is_string($value) ? trim(strip_tags($value)) : $value;

        $this->merge([
            'name'    => $clean($this->input('name')),
            'phone'   => $clean($this->input('phone')),
            'email'   => $clean($this->input('email')) ?: null,
            'address' => $clean($this->input('address')) ?: null,
            'comment' => $clean($this->input('comment')) ?: null,
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'name'    => ['required', 'string', 'min:2', 'max:100'],
            'phone'   => ['required', 'string', 'max:25', function (string $attribute, mixed $value, Closure $fail) {
                if (PakistaniPhone::normalize($value) === null) {
                    $fail('Please enter a valid Pakistani mobile number, e.g. 0300 1234567.');
                }
            }],
            'email'   => ['nullable', 'email:rfc', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];

        if (config('orders.turnstile.secret_key')) {
            $rules['cf-turnstile-response'] = ['required', 'string', new Turnstile($this->ip())];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required'                  => 'Please enter your name.',
            'phone.required'                 => 'Please enter your mobile number so we can confirm your order.',
            'cf-turnstile-response.required' => 'Please complete the security check.',
        ];
    }

    public function customer(): array
    {
        return [
            'customer_name'    => $this->validated('name'),
            'customer_phone'   => PakistaniPhone::normalize($this->validated('phone')),
            'customer_email'   => $this->validated('email'),
            'customer_address' => $this->validated('address'),
            'customer_comment' => $this->validated('comment'),
        ];
    }
}
