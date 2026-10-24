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
            'email'   => ['nullable', 'required_without:phone', 'email', 'max:255'],
            'phone'   => ['nullable', 'required_without:email', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ], [
            'email.required_without' => 'Please give us an email or a phone number so we can reply.',
            'phone.required_without' => 'Please give us a phone number or an email so we can reply.',
        ]);

        // Extra sanitization: strip HTML tags to avoid injection/XSS in stored data & emails
        $sanitized = array_map(function ($value) {
            return is_string($value) ? strip_tags($value) : $value;
        }, $validated);

        $settings = SiteSetting::current();
        $recipient = $settings->contact_recipient_email ?: config('orders.admin_email');

        // Save to DB (using sanitized values)
        $contactMessage = ContactMessage::create([
            'name'       => $sanitized['name'],
            'email'      => $sanitized['email'] ?? null,
            'phone'      => $sanitized['phone'] ?? null,
            'subject'    => $sanitized['subject'] ?? null,
            'message'    => $sanitized['message'],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        // Send email (safe, escaped output in the mailable view)
        // The message is saved either way (Admin → Contact messages); email is a bonus.
        // Failures are reported (Error log) but never shown to the visitor.
        if ($recipient) {
            try {
                Mail::to($recipient)->send(new \App\Mail\ContactFormSubmitted($contactMessage));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('success', 'Thank you for contacting us. We will get back to you soon.');
    }
}
