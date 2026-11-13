<?php
declare(strict_types=1);

namespace QuickDRY\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickDRY\Tests\Fixtures\Status;
use QuickDRY\Tests\Fixtures\Suit;
use QuickDRY\Tests\Fixtures\Typed;
use ReflectionProperty;

/** Tests for the global helper functions in QuickDRY/QuickDRY.php */
final class HelpersTest extends TestCase
{
    // ---- get_base_class -----------------------------------------------------

    public function testGetBaseClassStripsNamespace(): void
    {
        self::assertSame('HelpersTest', get_base_class($this));
        self::assertSame('stdClass', get_base_class(new \stdClass()));
    }

    // ---- base64 id-safe -----------------------------------------------------

    public static function base64Provider(): array
    {
        return [
            'plain text'        => ['hello world'],
            'needs + and /'     => ["\xfb\xff\xbf"],   // encodes to "+/+/"
            'one byte (pad 2)'  => ['a'],
            'two bytes (pad 1)' => ['ab'],
            'three (no pad)'    => ['abc'],
            'binary'            => [random_bytes(32)],
            'zero string'       => ['0'],
        ];
    }

    #[DataProvider('base64Provider')]
    public function testBase64IdSafeRoundTrip(string $data): void
    {
        $encoded = base64_encode_id_safe($data);

        self::assertIsString($encoded);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $encoded, 'must be usable as an HTML id');
        self::assertSame($data, base64_decode_id_safe($encoded));
    }

    public function testBase64IdSafeEmptyAndNull(): void
    {
        self::assertNull(base64_encode_id_safe(null));
        self::assertNull(base64_encode_id_safe(''));
        self::assertNull(base64_decode_id_safe(null));
        self::assertNull(base64_decode_id_safe(''));
    }

    // ---- arrays_diff_side_by_side ------------------------------------------

    public function testDiffReturnsOnlyDifferences(): void
    {
        $a = ['same' => 1, 'changed' => 'x', 'onlyA' => 'a'];
        $b = ['same' => 1, 'changed' => 'y', 'onlyB' => 'b'];

        self::assertSame([
            'changed' => ['a' => 'x', 'b' => 'y'],
            'onlyA'   => ['a' => 'a', 'b' => null],
            'onlyB'   => ['a' => null, 'b' => 'b'],
        ], arrays_diff_side_by_side($a, $b));
    }

    public function testDiffIgnoresSuperficialNumericDifferences(): void
    {
        self::assertSame([], arrays_diff_side_by_side(['amt' => '4871.87'], ['amt' => '4871.8700']));
        self::assertSame([], arrays_diff_side_by_side(['amt' => 10], ['amt' => '10.0']));
    }

    public function testDiffRespectsTolerance(): void
    {
        self::assertNotSame([], arrays_diff_side_by_side(['v' => 1.0], ['v' => 1.01]));
        self::assertSame([], arrays_diff_side_by_side(['v' => 1.0], ['v' => 1.01], 0.1));
    }

    public function testDiffRecursesIntoNestedArrays(): void
    {
        $a = ['row' => ['id' => 1, 'name' => 'A']];
        $b = ['row' => ['id' => 1, 'name' => 'B']];

        self::assertSame(['row' => ['name' => ['a' => 'A', 'b' => 'B']]], arrays_diff_side_by_side($a, $b));
    }

    public function testDiffOfIdenticalArraysIsEmpty(): void
    {
        $a = ['x' => [1, 2, ['y' => 'z']]];
        self::assertSame([], arrays_diff_side_by_side($a, $a));
    }

    // ---- arrays_are_equal ---------------------------------------------------

    public function testArraysAreEqual(): void
    {
        self::assertTrue(arrays_are_equal(['a' => 1, 'b' => [2, 3]], ['a' => 1, 'b' => [2, 3]]));
        self::assertTrue(arrays_are_equal([], []));
    }

    public function testArraysAreEqualIsStrictAboutTypes(): void
    {
        self::assertFalse(arrays_are_equal(['a' => 1], ['a' => '1']));
    }

    public function testArraysAreEqualIsStrictAboutKeyOrder(): void
    {
        self::assertFalse(arrays_are_equal(['a' => 1, 'b' => 2], ['b' => 2, 'a' => 1]));
    }

    public function testArraysAreEqualDetectsNestedDifference(): void
    {
        self::assertFalse(arrays_are_equal(['a' => ['b' => 1]], ['a' => ['b' => 2]]));
        self::assertFalse(arrays_are_equal(['a' => 1], ['a' => 1, 'b' => 2]));
    }

    // ---- coerce_to_reflection_type -----------------------------------------

    private static function type(string $property): ?\ReflectionType
    {
        return (new ReflectionProperty(Typed::class, $property))->getType();
    }

    public static function coerceProvider(): array
    {
        return [
            'int from string'          => ['int', '42', 42],
            'float from string'        => ['float', '1.5', 1.5],
            'string from int'          => ['string', 7, '7'],
            'bool "true"'              => ['bool', 'true', true],
            'bool "yes"'               => ['bool', 'yes', true],
            'bool "0"'                 => ['bool', '0', false],
            'bool garbage'             => ['bool', 'banana', false],
            'array from JSON'          => ['array', '{"a":1}', ['a' => 1]],
            'array wraps scalar'       => ['array', 'x', ['x']],
            'nullable empty string'    => ['nullableInt', '', null],
            'nullable null'            => ['nullableInt', null, null],
            'backed enum'              => ['suit', 'H', Suit::Hearts],
            'backed enum invalid'      => ['suit', 'Z', null],
            'pure enum by name'        => ['status', 'Inactive', Status::Inactive],
            'untyped passes through'   => ['untyped', ['raw'], ['raw']],
        ];
    }

    #[DataProvider('coerceProvider')]
    public function testCoerceToReflectionType(string $property, mixed $input, mixed $expected): void
    {
        self::assertSame($expected, coerce_to_reflection_type($input, self::type($property)));
    }

    public function testCoerceNullTypeReturnsValue(): void
    {
        self::assertSame('raw', coerce_to_reflection_type('raw', null));
    }

    public function testCoerceDate(): void
    {
        $result = coerce_to_reflection_type('2026-11-13 08:45:00', self::type('date'));

        self::assertInstanceOf(DateTimeImmutable::class, $result);
        self::assertSame('2026-11-13 08:45:00', $result->format('Y-m-d H:i:s'));
    }

    public function testCoerceInvalidDateIsNull(): void
    {
        self::assertNull(coerce_to_reflection_type('not a date', self::type('date')));
    }

    public function testCoerceUnionTypeUsesFirstWorkingMember(): void
    {
        $result = coerce_to_reflection_type('12', self::type('intOrString'));

        self::assertContains($result, [12, '12']);
    }

    // ---- safeBacktrace ------------------------------------------------------

    public function testSafeBacktraceHasNoObjectsOrResources(): void
    {
        $trace = $this->callSafeBacktrace(new \stdClass(), fopen('php://memory', 'r'));

        $json = json_encode($trace, JSON_THROW_ON_ERROR);
        self::assertStringContainsString('callSafeBacktrace', $json);
    }

    private function callSafeBacktrace(object $o, $resource): array
    {
        try {
            return safeBacktrace(5);
        } finally {
            fclose($resource);
        }
    }
}
