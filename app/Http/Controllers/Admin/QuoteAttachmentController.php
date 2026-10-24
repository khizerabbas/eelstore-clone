<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// Download a customer's uploaded list (private disk) – admins only
class QuoteAttachmentController extends Controller
{
    public function __invoke(Request $request, QuoteRequest $quote)
    {
        abort_unless($request->user()?->canAccessPanel(Filament::getPanel('admin')) && $request->user()->can('view', $quote), 403);
        abort_unless($quote->attachment_path && Storage::disk(QuoteRequest::ATTACHMENT_DISK)->exists($quote->attachment_path), 404);

        return Storage::disk(QuoteRequest::ATTACHMENT_DISK)->download(
            $quote->attachment_path,
            $quote->attachment_name ?: basename($quote->attachment_path),
            ['X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'"]
        );
    }
}
