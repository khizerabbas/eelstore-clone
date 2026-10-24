<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum QuoteStatus: string implements HasColor, HasLabel
{
    case New       = 'new';
    case Contacted = 'contacted';
    case Quoted    = 'quoted';
    case Won       = 'won';
    case Lost      = 'lost';

    public function getLabel(): string
    {
        return match ($this) {
            self::New       => 'New',
            self::Contacted => 'Contacted',
            self::Quoted    => 'Quote sent',
            self::Won       => 'Won (became an order)',
            self::Lost      => 'Lost / not interested',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New       => 'warning',
            self::Contacted => 'info',
            self::Quoted    => 'primary',
            self::Won       => 'success',
            self::Lost      => 'gray',
        };
    }
}
