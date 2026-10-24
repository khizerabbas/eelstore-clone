<?php

namespace Tests\Unit;

use App\Support\PakistaniPhone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PakistaniPhoneTest extends TestCase
{
    public static function validNumbers(): array
    {
        return [
            ['03001234567'],
            ['0300 1234567'],
            ['0300-1234567'],
            ['3001234567'],
            ['923001234567'],
            ['+92 300 1234567'],
            ['+92-300-1234567'],
            ['00923001234567'],
            ['(0300) 123 4567'],
        ];
    }

    #[DataProvider('validNumbers')]
    public function test_valid_numbers_normalise_to_e164(string $input): void
    {
        $this->assertSame('+923001234567', PakistaniPhone::normalize($input));
    }

    public static function invalidNumbers(): array
    {
        return [
            [''],
            ['abc'],
            ['0300123456'],      // too short
            ['030012345678'],    // too long
            ['0421234567'],      // landline
            ['+443001234567'],   // other country
            ['0300 12a4567'],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_invalid_numbers_are_rejected(string $input): void
    {
        $this->assertNull(PakistaniPhone::normalize($input));
    }

    public function test_display_format(): void
    {
        $this->assertSame('0300 1234567', PakistaniPhone::display('+923001234567'));
    }
}
