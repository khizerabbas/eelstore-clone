<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuoteRequestForm;
use App\Jobs\SendQuoteWhatsAppAlert;
use App\Mail\NewQuoteRequest;
use App\Models\QuoteRequest;
use App\Models\SiteSetting;
use App\Support\Cart;
use App\Support\PakistaniPhone;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Throwable;

// Bulk / trade quote requests (/bulk-quote) – same notifications as orders (email + owner WhatsApp)
class QuoteController extends Controller
{
    public function create()
    {
        $cartItems = Cart::items();

        return view('quotes.create', [
            'cartItems' => $cartItems,
            'cartTotal' => $cartItems->sum('cart_subtotal'),
        ]);
    }

    public function store(QuoteRequestForm $request)
    {
        // Honeypot: pretend it worked, store nothing
        if ($request->filled('website')) {
            return redirect()->route('quotes.thanks');
        }

        $phone    = PakistaniPhone::normalize($request->validated('phone'));
        $ipKey    = 'quote:ip:' . $request->ip();
        $phoneKey = 'quote:phone:' . $phone;

        if (RateLimiter::tooManyAttempts($ipKey, 5) || RateLimiter::tooManyAttempts($phoneKey, 3)) {
            return back()
                ->withErrors(['form' => 'You have sent several requests recently. Please wait a while, or call/WhatsApp us directly.'])
                ->withInput();
        }

        $attachmentPath = $attachmentName = null;
        if ($file = $request->file('attachment')) {
            // Random name on the private disk – never served directly
            $attachmentPath = $file->storeAs('quotes', Str::random(40) . '.' . $file->guessExtension(), QuoteRequest::ATTACHMENT_DISK);
            $attachmentName = Str::limit(preg_replace('/[^\w.\- ]+/', '_', $file->getClientOriginalName()), 120, '');
        }

        $cartItems = $request->boolean('include_cart')
            ? Cart::items()->map(fn ($p) => [
                'product_id'   => $p->id,
                'product_name' => $p->name,
                'sku'          => $p->sku,
                'quantity'     => $p->cart_quantity,
                'unit_price'   => $p->cart_price,
                'line_total'   => $p->cart_subtotal,
            ])->values()->all()
            : null;

        $quote = QuoteRequest::create([
            'name'            => $request->validated('name'),
            'phone'           => $phone,
            'email'           => $request->validated('email'),
            'business_name'   => $request->validated('business_name'),
            'city'            => $request->validated('city'),
            'items'           => (string) $request->validated('items'),
            'cart_items'      => $cartItems ?: null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'ip_address'      => $request->ip(),
            'user_agent'      => Str::limit((string) $request->userAgent(), 1000, ''),
        ]);

        RateLimiter::hit($ipKey, 3600);
        RateLimiter::hit($phoneKey, 3600);

        // A notification problem must never fail the customer's request (failures land in the Error log)
        $notify = function (callable $send) {
            try {
                $send();
            } catch (Throwable $e) {
                report($e);
            }
        };

        $adminEmail = config('orders.admin_email') ?: SiteSetting::current()->contact_recipient_email;
        if ($adminEmail) {
            $notify(fn () => Mail::to($adminEmail)->queue(new NewQuoteRequest($quote)));
        }
        $notify(fn () => SendQuoteWhatsAppAlert::dispatch($quote));

        return redirect()->route('quotes.thanks')->with('quote_reference', $quote->reference);
    }

    public function thanks()
    {
        return response()
            ->view('quotes.thanks', ['reference' => session('quote_reference')])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
