<?php

namespace App\Filament\Resources\ErrorLogs;

use App\Filament\Resources\ErrorLogs\Pages\ManageErrorLogs;
use App\Models\ErrorLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

// Errors seen by the site and its visitors, grouped (Owner only – see ErrorLogPolicy)
class ErrorLogResource extends Resource
{
    protected static ?string $model = ErrorLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBugAnt;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Error log';

    protected static ?string $modelLabel = 'error';

    protected static ?string $pluralModelLabel = 'error log';

    protected static ?int $navigationSort = 4;

    public const SOURCES = [
        'server'  => 'Server (website crashed)',
        'http'    => 'Visitor problem (expired page, rate limit…)',
        'queue'   => 'Background job (email / WhatsApp)',
        'browser' => 'Visitor\'s browser',
        'console' => 'Scheduled task / command',
    ];

    public static function getNavigationBadge(): ?string
    {
        if (! static::canViewAny()) {
            return null;
        }

        $count = ErrorLog::whereNull('resolved_at')->where('level', 'error')->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('What happened')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('message')->columnSpanFull()->weight('bold'),
                        TextEntry::make('source')->formatStateUsing(fn ($state) => self::SOURCES[$state] ?? $state),
                        TextEntry::make('level')->badge()->color(fn ($state) => $state === 'error' ? 'danger' : 'warning'),
                        TextEntry::make('type'),
                        TextEntry::make('occurrences')->label('Times seen'),
                        TextEntry::make('first_seen_at')->label('First seen')->dateTime('d M Y, h:i A'),
                        TextEntry::make('last_seen_at')->label('Last seen')->dateTime('d M Y, h:i A'),
                        TextEntry::make('resolved_at')->label('Resolved')->dateTime('d M Y, h:i A')->placeholder('Not resolved'),
                    ]),
                Section::make('Where (latest occurrence)')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('url')->label('Page')->placeholder('—')->columnSpanFull()->copyable(),
                        TextEntry::make('method')->placeholder('—'),
                        TextEntry::make('file')
                            ->label('Code location')
                            ->formatStateUsing(fn (ErrorLog $record) => $record->file . ($record->line ? ':' . $record->line : ''))
                            ->placeholder('—'),
                        TextEntry::make('user.name')->label('Admin logged in')->placeholder('Visitor (not logged in)'),
                        TextEntry::make('ip_address')->label('IP')->placeholder('—'),
                        TextEntry::make('user_agent')->label('Browser')->placeholder('—')->columnSpanFull(),
                    ]),
                Section::make('Details')
                    ->columnSpanFull()
                    ->collapsed()
                    ->visible(fn (ErrorLog $record) => filled($record->context))
                    ->schema([
                        KeyValueEntry::make('context')
                            ->hiddenLabel()
                            ->state(fn (ErrorLog $record) => collect($record->context)
                                ->map(fn ($v) => is_scalar($v) || $v === null ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT))
                                ->all()),
                    ]),
                Section::make('Stack trace (for the developer)')
                    ->columnSpanFull()
                    ->collapsed()
                    ->visible(fn (ErrorLog $record) => filled($record->trace))
                    ->schema([
                        TextEntry::make('trace')
                            ->hiddenLabel()
                            ->formatStateUsing(fn (?string $state) => '<pre class="text-xs whitespace-pre-wrap break-all">' . e($state) . '</pre>')
                            ->html(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_seen_at', 'desc')
            ->columns([
                TextColumn::make('last_seen_at')
                    ->label('Last seen')
                    ->since()
                    ->dateTimeTooltip('d M Y, h:i A')
                    ->sortable(),
                TextColumn::make('level')
                    ->badge()
                    ->color(fn ($state) => $state === 'error' ? 'danger' : 'warning'),
                TextColumn::make('source')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                TextColumn::make('message')
                    ->searchable()
                    ->limit(90)
                    ->wrap()
                    ->description(fn (ErrorLog $record) => $record->url ? parse_url($record->url, PHP_URL_PATH) : $record->type),
                TextColumn::make('occurrences')
                    ->label('Times')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('resolved_at')
                    ->label('Resolved')
                    ->boolean()
                    ->state(fn (ErrorLog $record) => $record->resolved_at !== null),
            ])
            ->filters([
                TernaryFilter::make('resolved_at')
                    ->label('Resolved')
                    ->nullable()
                    ->default(false),
                SelectFilter::make('source')->options(self::SOURCES),
                SelectFilter::make('level')->options(['error' => 'Error', 'warning' => 'Warning']),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('resolve')
                    ->label('Resolve')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (ErrorLog $record) => $record->resolved_at === null)
                    ->action(fn (ErrorLog $record) => $record->update(['resolved_at' => now()])),
                Action::make('reopen')
                    ->label('Reopen')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('gray')
                    ->visible(fn (ErrorLog $record) => $record->resolved_at !== null)
                    ->action(fn (ErrorLog $record) => $record->update(['resolved_at' => null])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('resolve')
                        ->label('Mark resolved')
                        ->icon(Heroicon::OutlinedCheck)
                        ->action(fn (Collection $records) => ErrorLog::whereKey($records->modelKeys())->update(['resolved_at' => now()]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No errors 🎉')
            ->emptyStateDescription('Errors seen by the website or by visitors will appear here.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageErrorLogs::route('/'),
        ];
    }
}
