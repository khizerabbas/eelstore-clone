<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

// Cloudflare Turnstile server-side check. Only used when TURNSTILE_SECRET_KEY is set.
class Turnstile implements ValidationRule
{
    public function __construct(private readonly ?string $ip = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $response = Http::asForm()
            ->timeout(10)
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret'   => config('orders.turnstile.secret_key'),
                'response' => (string) $value,
                'remoteip' => $this->ip,
            ]);

        if (! $response->ok() || ! $response->json('success')) {
            $fail('Please complete the security check and try again.');
        }
    }
}
