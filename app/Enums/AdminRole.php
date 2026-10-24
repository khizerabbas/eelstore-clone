<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

// Only admins have accounts. Owner: everything. Staff: orders, catalog, homepage, messages.
enum AdminRole: string implements HasLabel
{
    case Owner = 'owner';
    case Staff = 'staff';

    public function getLabel(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Staff => 'Staff',
        };
    }
}
