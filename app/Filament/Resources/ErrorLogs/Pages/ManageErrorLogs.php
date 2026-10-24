<?php

namespace App\Filament\Resources\ErrorLogs\Pages;

use App\Filament\Resources\ErrorLogs\ErrorLogResource;
use App\Models\ErrorLog;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

// Errors are only written by the app – the page offers clean-up instead of "create"
class ManageErrorLogs extends ManageRecords
{
    protected static string $resource = ErrorLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearResolved')
                ->label('Clear resolved')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Deletes every error marked as resolved.')
                ->authorize(fn () => ErrorLogResource::canDeleteAny())
                ->action(function () {
                    $count = ErrorLog::whereNotNull('resolved_at')->delete();
                    Notification::make()->title("Deleted {$count} resolved error(s)")->success()->send();
                }),
            Action::make('clearAll')
                ->label('Clear all')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Deletes the whole error log. New errors will still be recorded.')
                ->authorize(fn () => ErrorLogResource::canDeleteAny())
                ->action(function () {
                    $count = ErrorLog::query()->delete();
                    Notification::make()->title("Deleted {$count} error(s)")->success()->send();
                }),
        ];
    }
}
