<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

// Order actions shared by the orders table and the order view page
class OrderActions
{
    public static function whatsapp(): Action
    {
        return Action::make('whatsapp')
            ->label('WhatsApp')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('success')
            ->url(fn (Order $record) => $record->whatsappUrl(
                'Assalam o Alaikum ' . $record->customer_name . ', this is ' . \App\Models\SiteSetting::storeName()
                . ' about your order request ' . $record->reference . '.'
            ), shouldOpenInNewTab: true);
    }

    public static function call(): Action
    {
        return Action::make('call')
            ->label('Call')
            ->icon(Heroicon::OutlinedPhone)
            ->color('gray')
            ->url(fn (Order $record) => 'tel:' . $record->customer_phone);
    }

    // One click for the most common step: we reached out to the customer
    public static function markContacted(): Action
    {
        return Action::make('markContacted')
            ->label('Mark contacted')
            ->icon(Heroicon::OutlinedCheck)
            ->color('info')
            ->visible(fn (Order $record) => $record->status === OrderStatus::New)
            ->requiresConfirmation()
            ->modalDescription('The customer will see "We contacted you – check your WhatsApp / email" on their order page.')
            ->action(function (Order $record) {
                $record->markStatus(OrderStatus::Contacted, by: auth()->user()?->name);

                Notification::make()->title("{$record->reference} marked as contacted")->success()->send();
            });
    }

    public static function changeStatus(): Action
    {
        return Action::make('changeStatus')
            ->label('Change status')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('primary')
            ->fillForm(fn (Order $record) => ['status' => $record->status])
            ->schema([
                Select::make('status')
                    ->options(OrderStatus::class)
                    ->required()
                    ->native(false)
                    ->selectablePlaceholder(false),
                Textarea::make('note')
                    ->label('Note (optional, internal)')
                    ->rows(2)
                    ->maxLength(500),
            ])
            ->action(function (Order $record, array $data) {
                $status = $data['status'] instanceof OrderStatus ? $data['status'] : OrderStatus::from($data['status']);

                $record->markStatus($status, $data['note'] ?? null, auth()->user()?->name);

                Notification::make()->title("{$record->reference} is now {$status->getLabel()}")->success()->send();
            });
    }

    public static function printSlip(): Action
    {
        return Action::make('print')
            ->label('Print slip')
            ->icon(Heroicon::OutlinedPrinter)
            ->color('gray')
            ->url(fn (Order $record) => route('admin.orders.print', $record->id), shouldOpenInNewTab: true);
    }

    public static function customerPage(): Action
    {
        return Action::make('customerPage')
            ->label('Customer view')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->url(fn (Order $record) => route('orders.show', $record), shouldOpenInNewTab: true);
    }
}
