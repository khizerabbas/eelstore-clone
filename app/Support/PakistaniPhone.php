<?php

namespace App\Support;

/**
 * Pakistani mobile numbers (3XX XXXXXXX).
 * Accepts 03001234567, 3001234567, 923001234567, +92 300 1234567, 0092-300-1234567 …
 * and normalises them to E.164: +923001234567.
 */
class PakistaniPhone
{
    public static function normalize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $digits = preg_replace('/[\s\-\.\(\)]/', '', trim($input));

        if (str_starts_with($digits, '+')) {
            $digits = substr($digits, 1);
        } elseif (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (! ctype_digit($digits)) {
            return null;
        }

        if (str_starts_with($digits, '92')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        // Mobile numbers: 3 followed by 9 digits
        if (! preg_match('/^3\d{9}$/', $digits)) {
            return null;
        }

        return '+92' . $digits;
    }

    // +923001234567 → 0300 1234567
    public static function display(?string $e164): string
    {
        if (! $e164 || ! preg_match('/^\+92(3\d{2})(\d{7})$/', $e164, $m)) {
            return (string) $e164;
        }

        return '0' . $m[1] . ' ' . $m[2];
    }
}
