<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Owner-only list of backup zips (database + uploads) made nightly by the scheduler (routes/console.php).
 * Download one regularly and keep it off the server.
 */
class Backups extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Backups';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.backups';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isOwner();
    }

    /** @return list<array{path: string, name: string, size: string, date: \Illuminate\Support\Carbon}> */
    public function getBackups(): array
    {
        $disk = Storage::disk('backups');

        return collect($disk->allFiles())
            ->filter(fn (string $path) => str_ends_with($path, '.zip'))
            ->map(fn (string $path) => [
                'path' => $path,
                'name' => basename($path),
                'size' => Number::fileSize($disk->size($path), precision: 1),
                'date' => \Illuminate\Support\Carbon::createFromTimestamp($disk->lastModified($path))->timezone(config('app.timezone')),
            ])
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    public function download(string $path): StreamedResponse
    {
        abort_unless(static::canAccess(), 403);

        // Only files that really are in the backups folder (no "../" tricks)
        abort_unless(in_array($path, array_column($this->getBackups(), 'path'), true), 404);

        return Storage::disk('backups')->download($path);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backupNow')
                ->label('Back up now')
                ->icon(Heroicon::OutlinedPlay)
                ->requiresConfirmation()
                ->modalDescription('Starts a backup of the database and uploaded files in the background. It appears in this list within a few minutes (the cron job must be running).')
                ->action(function () {
                    abort_unless(static::canAccess(), 403);

                    Artisan::queue('backup:run');

                    Notification::make()->title('Backup started')->body('Refresh this page in a few minutes.')->success()->send();
                }),
        ];
    }
}
