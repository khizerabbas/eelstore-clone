<?php

namespace App\Http\Requests;

use App\Rules\Turnstile;
use App\Support\PakistaniPhone;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

// Validation for /bulk-quote
class QuoteRequestForm extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $clean = fn ($value) => is_string($value) ? trim(strip_tags($value)) : $value;

        $this->merge([
            'name'          => $clean($this->input('name')),
            'phone'         => $clean($this->input('phone')),
            'email'         => $clean($this->input('email')) ?: null,
            'business_name' => $clean($this->input('business_name')) ?: null,
            'city'          => $clean($this->input('city')) ?: null,
            'items'         => $clean($this->input('items')),
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'name'          => ['required', 'string', 'min:2', 'max:100'],
            'phone'         => ['required', 'string', 'max:25', function (string $attribute, mixed $value, Closure $fail) {
                if (PakistaniPhone::normalize($value) === null) {
                    $fail('Please enter a valid Pakistani mobile number, e.g. 0300 1234567.');
                }
            }],
            'email'         => ['nullable', 'email:rfc', 'max:150'],
            'business_name' => ['nullable', 'string', 'max:150'],
            'city'          => ['nullable', 'string', 'max:100'],
            'items'         => ['required_without:attachment', 'nullable', 'string', 'max:5000'],
            // Photo or PDF of their list – checked by real file content, not just the name
            'attachment'    => ['nullable', File::types(['jpg', 'jpeg', 'png', 'webp', 'pdf'])->max(10 * 1024)],
            'include_cart'  => ['nullable', 'boolean'],
        ];

        if (config('orders.turnstile.secret_key')) {
            $rules['cf-turnstile-response'] = ['required', 'string', new Turnstile($this->ip())];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'items.required_without' => 'Please list the items you need, or attach a photo/PDF of your list.',
            'attachment.max'         => 'The file must be 10 MB or smaller.',
        ];
    }
}
