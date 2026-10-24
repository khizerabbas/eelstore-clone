<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Support\PakistaniPhone;
use Closure;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

// Edit customer details and the internal note. Status changes go through the "Change status" action.
class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer')
                    ->columns(2)
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Name')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('customer_phone')
                            ->label('Mobile')
                            ->tel()
                            ->required()
                            ->formatStateUsing(fn (?string $state) => PakistaniPhone::display($state))
                            ->dehydrateStateUsing(fn (?string $state) => PakistaniPhone::normalize($state))
                            ->rules([fn () => function (string $attribute, mixed $value, Closure $fail) {
                                if (PakistaniPhone::normalize($value) === null) {
                                    $fail('Enter a valid Pakistani mobile number, e.g. 0300 1234567.');
                                }
                            }]),
                        TextInput::make('customer_email')
                            ->label('Email')
                            ->email()
                            ->maxLength(150),
                        Textarea::make('customer_address')
                            ->label('Address')
                            ->rows(2)
                            ->maxLength(500),
                        Textarea::make('customer_comment')
                            ->label('Customer comment')
                            ->rows(2)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),
                Section::make('Internal')
                    ->schema([
                        Textarea::make('admin_note')
                            ->label('Admin note (never shown to the customer)')
                            ->rows(5)
                            ->maxLength(5000),
                    ]),
            ]);
    }
}
