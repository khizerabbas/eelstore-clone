<?php

namespace App\Filament\Pages\Website;

use App\Models\SiteContent;
use App\Support\Content;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use UnitEnum;

/**
 * Base for the "Website" admin pages. Each page edits one or more content groups
 * (config/site_content.php); field names are "group.key", e.g. "home.hero_title".
 */
abstract class ContentPage extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Website';

    /** @var list<string> content groups edited on this page */
    protected static array $groups = [];

    public ?array $data = [];

    /** @return array<\Filament\Schemas\Components\Component> */
    abstract protected function fields(): array;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isOwner();
    }

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $state = [];

        foreach (static::$groups as $group) {
            $state[$group] = Content::group($group);
        }

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components($this->fields());
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Save changes')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                        Action::make('reset')
                            ->label('Reset this page to defaults')
                            ->color('gray')
                            ->requiresConfirmation()
                            ->modalDescription('All values on this page go back to the original defaults. Uploaded images stay in storage.')
                            ->action(fn () => $this->resetToDefaults()),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $state = $this->form->getState();

        foreach (static::$groups as $group) {
            Content::saveGroup($group, array_map([self::class, 'normalise'], $state[$group] ?? []));
        }

        Notification::make()->title('Saved – the website is updated')->success()->send();
    }

    public function resetToDefaults(): void
    {
        abort_unless(static::canAccess(), 403);

        foreach (static::$groups as $group) {
            SiteContent::where('key', 'like', $group . '.%')->delete();
        }

        Content::flush();
        $this->fillForm();

        Notification::make()->title('Defaults restored')->success()->send();
    }

    // Repeater state is keyed by random ids – store plain lists
    private static function normalise(mixed $value): mixed
    {
        if (is_array($value) && ! array_is_list($value) && collect($value)->every(fn ($v) => is_array($v))) {
            return array_values($value);
        }

        return $value;
    }

    // Link field: "/path" inside the site or a full http(s) URL
    protected static function linkInput(string $name, string $label = 'Link'): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->placeholder('/shop or https://…')
            ->maxLength(255)
            ->rule(fn () => function (string $attribute, mixed $value, Closure $fail) {
                if (filled($value) && Content::link($value) === null) {
                    $fail('Use a path on this site like /shop, or a full link starting with https://');
                }
            });
    }

    protected static function placeholdersHint(): string
    {
        return 'You can use {store} for the store name and {year} for the current year.';
    }
}
