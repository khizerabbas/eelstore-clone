<?php

namespace App\Support;

use Filament\Forms\Components\RichEditor\RichContentRenderer;

// Safe output for admin-edited rich text (product descriptions, CMS pages)
class RichText
{
    public static function render(?string $content): string
    {
        if (blank($content)) {
            return '';
        }

        // Older plain-text content: keep line breaks
        if (strip_tags($content) === $content) {
            return nl2br(e($content));
        }

        // Sanitised HTML (strips scripts, event handlers, unsafe URLs)
        return RichContentRenderer::make($content)->toHtml();
    }
}
