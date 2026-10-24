<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->description(fn (User $record) => $record->email),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn (User $record) => $record->isOwner() ? 'primary' : 'gray'),
                IconColumn::make('is_active')
                    ->label('Can log in')
                    ->boolean(),
                IconColumn::make('two_factor')
                    ->label('2FA')
                    ->state(fn (User $record) => filled($record->app_authentication_secret))
                    ->boolean(),
                TextColumn::make('last_login_at')
                    ->label('Last login')
                    ->since()
                    ->placeholder('Never'),
            ])
            ->recordActions([
                EditAction::make(),
                // For a lost phone: lets the user log in with just the password and set 2FA up again
                Action::make('reset2fa')
                    ->label('Reset 2FA')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('warning')
                    ->visible(fn (User $record) => filled($record->app_authentication_secret) && $record->isNot(auth()->user()))
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->forceFill([
                            'app_authentication_secret'         => null,
                            'app_authentication_recovery_codes' => null,
                        ])->save();

                        Notification::make()->title("2FA reset for {$record->name}")->success()->send();
                    }),
                DeleteAction::make(),
            ]);
    }
}
