<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Store details used across the storefront (header, footer, contact page, home hero).
 * Email/WhatsApp notification credentials live in .env on purpose (see config/orders.php).
 */
class SiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Store & contact';

    protected static ?string $title = 'Store & contact';

    protected static ?int $navigationSort = 0;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isOwner();
    }

    public function mount(): void
    {
        $this->form->fill(SiteSetting::current()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        $link = fn (string $name, string $label) => TextInput::make($name)
            ->label($label)
            ->url()
            ->rule('regex:/^https?:\/\//i') // blocks javascript: and other schemes
            ->maxLength(255);

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Store')
                    ->columns(2)
                    ->schema([
                        TextInput::make('store_name')
                            ->label('Store name')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('opening_hours')
                            ->label('Opening hours')
                            ->placeholder('Mon–Sat 10:00 am – 8:00 pm')
                            ->maxLength(255),
                        Textarea::make('store_address')
                            ->label('Address')
                            ->rows(3)
                            ->maxLength(500),
                    ]),
                Section::make('Contact')
                    ->columns(2)
                    ->schema([
                        TextInput::make('contact_phone')
                            ->label('Phone (shown on the site)')
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('contact_email')
                            ->label('Email (shown on the site)')
                            ->email()
                            ->maxLength(150),
                        TextInput::make('contact_recipient_email')
                            ->label('Contact form messages go to')
                            ->email()
                            ->maxLength(150)
                            ->helperText('Also used for order emails when ORDER_ADMIN_EMAIL is not set.'),
                        $link('whatsapp_url', 'WhatsApp link')
                            ->placeholder('https://wa.me/923001234567')
                            ->helperText('Used by "Message us on WhatsApp" buttons. Leave empty to use the phone number above.'),
                        Textarea::make('map_embed_url')
                            ->label('Google Maps embed')
                            ->rows(2)
                            ->helperText('Google Maps → Share → Embed a map → copy HTML, and paste it here (the link is extracted automatically).')
                            ->dehydrateStateUsing(fn (?string $state) => SiteSetting::extractMapSrc($state))
                            ->rule(fn () => function (string $attribute, $value, \Closure $fail) {
                                if (filled($value) && SiteSetting::extractMapSrc($value) === null) {
                                    $fail('Paste the "Embed a map" code from Google Maps (it starts with https://www.google.com/maps/embed).');
                                }
                            })
                            ->columnSpanFull(),
                    ]),
                Section::make('Social media')
                    ->columns(2)
                    ->schema([
                        $link('facebook_url', 'Facebook'),
                        $link('instagram_url', 'Instagram'),
                        $link('youtube_url', 'YouTube'),
                        $link('x_url', 'X (Twitter)'),
                    ]),
            ]);
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
                            ->label('Save settings')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        SiteSetting::current()->update($this->form->getState());

        Notification::make()->title('Settings saved')->success()->send();
    }
}
