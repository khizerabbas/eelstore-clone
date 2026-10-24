<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\AdminRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        // Owners can't demote or deactivate themselves (avoids locking everyone out)
        $isSelf = fn (?User $record) => $record && $record->is(auth()->user());

        return $schema
            ->columns(1)
            ->components([
                Section::make('Admin user')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(150)
                            ->unique(ignoreRecord: true),
                        Select::make('role')
                            ->options(AdminRole::class)
                            ->default(AdminRole::Staff)
                            ->required()
                            ->native(false)
                            ->helperText('Owner: everything incl. settings and admin users. Staff: orders, catalog, homepage, messages (cannot delete).')
                            ->disabled($isSelf),
                        Toggle::make('is_active')
                            ->label('Can log in')
                            ->default(true)
                            ->disabled($isSelf)
                            ->inline(false),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->rule(Password::min(12)->letters()->mixedCase()->numbers()->symbols())
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->helperText(fn (string $operation) => $operation === 'edit'
                                ? 'Leave empty to keep the current password. Min 12 characters with upper/lower case, numbers and symbols.'
                                : 'Min 12 characters with upper/lower case, numbers and symbols.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
