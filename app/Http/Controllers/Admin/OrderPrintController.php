<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SiteSetting;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

// Printable packing slip for an order (admin only)
class OrderPrintController extends Controller
{
    public function __invoke(Request $request, Order $order)
    {
        abort_unless($request->user()?->canAccessPanel(Filament::getPanel('admin')), 403);

        return response()
            ->view('admin.orders.print', [
                'order'    => $order->load('items'),
                'settings' => SiteSetting::current(),
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
