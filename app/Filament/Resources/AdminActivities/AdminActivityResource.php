<?php

namespace App\Filament\Resources\AdminActivities;

use App\Filament\Resources\AdminActivities\Pages\ManageAdminActivities;
use App\Models\AdminActivity;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

// Read-only log of admin actions (Owner only – see AdminActivityPolicy)
class AdminActivityResource extends Resource
{
    protected static ?string $model = AdminActivity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Activity log';

    protected static ?string $modelLabel = 'activity';

    protected static ?string $pluralModelLabel = 'activity log';

    protected static ?int $navigationSort = 3;

    public const ACTION_COLORS = [
        'created'  => 'success',
        'updated'  => 'info',
        'deleted'  => 'danger',
        'settings' => 'warning',
        'login'    => 'gray',
    ];

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('created_at')->label('When')->dateTime('d M Y, h:i:s A'),
                TextEntry::make('user_name')->label('Admin')->placeholder('—'),
                TextEntry::make('action')->badge()->color(fn (string $state) => self::ACTION_COLORS[$state] ?? 'gray'),
                TextEntry::make('subject_label')->label('What')->placeholder('—'),
                TextEntry::make('ip_address')->label('IP address')->placeholder('—'),
                KeyValueEntry::make('changes')
                    ->label('Changes (old → new)')
                    ->keyLabel('Field')
                    ->valueLabel('Change')
                    ->state(fn (AdminActivity $record) => collect($record->changes ?? [])
                        ->map(fn ($c) => self::show($c['old'] ?? null) . '  →  ' . self::show($c['new'] ?? null))
                        ->all())
                    ->visible(fn (AdminActivity $record) => filled($record->changes))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('d M Y, h:i A')
                    ->description(fn (AdminActivity $record) => $record->created_at?->diffForHumans())
                    ->sortable(),
                TextColumn::make('user_name')
                    ->label('Admin')
                    ->searchable(),
                TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state) => self::ACTION_COLORS[$state] ?? 'gray'),
                TextColumn::make('subject_label')
                    ->label('What')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('changes')
                    ->label('Changed fields')
                    ->state(fn (AdminActivity $record) => implode(', ', array_keys($record->changes ?? [])) ?: '—')
                    ->limit(60)
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('action')->options(array_combine(array_keys(self::ACTION_COLORS), array_map('ucfirst', array_keys(self::ACTION_COLORS)))),
                SelectFilter::make('user_id')->label('Admin')->relationship('user', 'name'),
                Filter::make('created_at')
                    ->schema([DatePicker::make('from'), DatePicker::make('until')])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAdminActivities::route('/'),
        ];
    }

    private static function show(mixed $value): string
    {
        return match (true) {
            $value === null, $value === '' => '(empty)',
            is_bool($value) => $value ? 'yes' : 'no',
            default => (string) $value,
        };
    }
}
