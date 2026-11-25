<?php
declare(strict_types=1);

namespace QuickDRY\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickDRY\Utilities\Strings;

final class StringsNumericTest extends TestCase
{
    public static function scientificProvider(): array
    {
        return [
            'whole number keeps zeros' => ['1.5e3', '1500'],
            'power of ten'             => ['1e5', '100000'],
            'negative'                 => ['-2.5E2', '-250'],
            'small'                    => ['1.23e-2', '0.0123'],
            'zero'                     => ['0e5', 0],
            'big'                      => ['1.0E+25', '10000000000000000000000000'],
        ];
    }

    #[DataProvider('scientificProvider')]
    public function testScientificNotation(string $value, string|int $expected): void
    {
        self::assertSame($expected, Strings::Numeric($value));
    }
}
