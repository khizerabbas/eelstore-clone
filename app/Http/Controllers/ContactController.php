<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class ContactController extends Controller
{
    public function index()
    {
        $settings = SiteSetting::current();

        return view('contact', compact('settings'));
    }

    public function submit(Request $request)
    {
        // Honeypot field to catch bots
        if ($request->filled('website')) {
            // silently ignore or pretend success
            return back()->with('success', 'Thank you, your message has been received.');
        }

        // Rate limit: max 5 attempts per 10 minutes per IP
        $key = 'contact:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()
                ->withErrors(['form' => "Too many attempts. Please try again in {$seconds} seconds."])
                ->withInput();
        }
        RateLimiter::hit($key, 600); // 10 minutes

        // Validate inputs (basic restrictions)
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        // Extra sanitization: strip HTML tags to avoid injection/XSS in stored data & emails
        $sanitized = array_map(function ($value) {
            return is_string($value) ? strip_tags($value) : $value;
        }, $validated);

        $settings = SiteSetting::current();
        $recipient = $settings->contact_recipient_email ?: 'test@test.com';

        // Save to DB (using sanitized values)
        $contactMessage = ContactMessage::create([
            'name'       => $sanitized['name'],
            'email'      => $sanitized['email'],
            'phone'      => $sanitized['phone'] ?? null,
            'subject'    => $sanitized['subject'] ?? null,
            'message'    => $sanitized['message'],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        // Send email (safe, escaped output in the mailable view)
        try {
            Mail::to($recipient)->send(new \App\Mail\ContactFormSubmitted($contactMessage));
        } catch (\Throwable $e) {
            // log error, but don't expose details to user
            // logger($e->getMessage());
        }

        return back()->with('success', 'Thank you for contacting us. We will get back to you soon.');
    }
}
