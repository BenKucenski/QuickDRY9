<?php
declare(strict_types=1);

namespace QuickDRY\Tests;

use DateTime;
use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickDRY\Connectors\mssql\MSSQL_Core;

/** A generated-style class with one column of every SQL Server type. */
class MSSQLAllTypes extends MSSQL_Core
{
    public static array $_primary = ['id'];

    protected static array $prop_definitions = [
        'id'          => ['type' => 'int', 'length' => '10,0', 'is_nullable' => false],
        'bit_nn'      => ['type' => 'bit', 'length' => '', 'is_nullable' => false],
        'bit_null'    => ['type' => 'bit', 'length' => '', 'is_nullable' => true],
        'tiny'        => ['type' => 'tinyint', 'length' => '3,0', 'is_nullable' => true],
        'small'       => ['type' => 'smallint', 'length' => '5,0', 'is_nullable' => true],
        'int_nn'      => ['type' => 'int', 'length' => '10,0', 'is_nullable' => false],
        'int_null'    => ['type' => 'int', 'length' => '10,0', 'is_nullable' => true],
        'big'         => ['type' => 'bigint', 'length' => '19,0', 'is_nullable' => true],
        'dec_null'    => ['type' => 'decimal', 'length' => '18,2', 'is_nullable' => true],
        'dec_nn'      => ['type' => 'decimal', 'length' => '18,2', 'is_nullable' => false],
        'dec_big'     => ['type' => 'decimal', 'length' => '38,4', 'is_nullable' => true],
        'dec_old'     => ['type' => 'decimal', 'length' => '18,10', 'is_nullable' => true], // generated before the scale fix
        'num'         => ['type' => 'numeric', 'length' => '18,2', 'is_nullable' => true],
        'money'       => ['type' => 'money', 'length' => '19,4', 'is_nullable' => false],
        'smallmoney'  => ['type' => 'smallmoney', 'length' => '10,4', 'is_nullable' => true],
        'flt'         => ['type' => 'float', 'length' => '53,', 'is_nullable' => true],
        'rl'          => ['type' => 'real', 'length' => '24,', 'is_nullable' => true],
        'chr'         => ['type' => 'char', 'length' => '10', 'is_nullable' => true],
        'vc'          => ['type' => 'varchar', 'length' => '50', 'is_nullable' => true],
        'nchr'        => ['type' => 'nchar', 'length' => '10', 'is_nullable' => true],
        'nvc'         => ['type' => 'nvarchar', 'length' => '-1', 'is_nullable' => true],
        'txt'         => ['type' => 'text', 'length' => '', 'is_nullable' => true],
        'ntxt'        => ['type' => 'ntext', 'length' => '', 'is_nullable' => true],
        'xml'         => ['type' => 'xml', 'length' => '-1', 'is_nullable' => true],
        'json'        => ['type' => 'json', 'length' => '', 'is_nullable' => true],
        'guid'        => ['type' => 'uniqueidentifier', 'length' => '', 'is_nullable' => true],
        'd'           => ['type' => 'date', 'length' => '', 'is_nullable' => true],
        'dt'          => ['type' => 'datetime', 'length' => '', 'is_nullable' => true],
        'dt2'         => ['type' => 'datetime2', 'length' => '', 'is_nullable' => true],
        'sdt'         => ['type' => 'smalldatetime', 'length' => '', 'is_nullable' => true],
        'dto'         => ['type' => 'datetimeoffset', 'length' => '', 'is_nullable' => true],
        'tm'          => ['type' => 'time', 'length' => '', 'is_nullable' => true],
        'bin'         => ['type' => 'binary', 'length' => '8', 'is_nullable' => true],
        'vbin'        => ['type' => 'varbinary', 'length' => '-1', 'is_nullable' => true],
        'img'         => ['type' => 'image', 'length' => '', 'is_nullable' => true],
        'rv'          => ['type' => 'timestamp', 'length' => '', 'is_nullable' => false],
        'geom'        => ['type' => 'geometry', 'length' => '', 'is_nullable' => true],
        'geog'        => ['type' => 'geography', 'length' => '', 'is_nullable' => true],
        'hier'        => ['type' => 'hierarchyid', 'length' => '', 'is_nullable' => true],
        'vec'         => ['type' => 'vector', 'length' => '', 'is_nullable' => true],
        'variant'     => ['type' => 'sql_variant', 'length' => '', 'is_nullable' => true],
        'handwritten' => ['type' => 'decimal(10,3)', 'length' => '', 'is_nullable' => true],
        'mystery'     => ['type' => 'not_a_type', 'length' => '', 'is_nullable' => true],
    ];

    public static function ST(string $name, mixed $value): mixed
    {
        return static::StrongType($name, $value);
    }

    public static function DateParam(string $name, DateTime $value): string
    {
        return static::SqlDateString($name, $value);
    }
}

final class MSSQLStrongTypeTest extends TestCase
{
    public static function conversionProvider(): array
    {
        return [
            // bit
            'bit "1"'                  => ['bit_nn', '1', 1],
            'bit 0'                    => ['bit_nn', 0, 0],
            'bit true'                 => ['bit_nn', true, 1],
            'bit "false"'              => ['bit_nn', 'false', 0],
            'bit blank not null'       => ['bit_nn', '', 0],
            'bit blank nullable'       => ['bit_null', '', null],
            // integers
            'tinyint string'           => ['tiny', '200', 200],
            'smallint negative'        => ['small', '-32768', -32768],
            'int from money text'      => ['int_null', '$1,234', 1234],
            'int drops fraction'       => ['int_null', '12.7', 12],
            'int null nullable'        => ['int_null', null, null],
            'int blank not null'       => ['int_nn', '', 0],
            'int "$0"'                 => ['int_nn', '$0', 0],
            'bigint max'               => ['big', '9223372036854775807', PHP_INT_MAX],
            'bigint from float'        => ['big', 5.0, 5],
            // decimal
            'decimal null stays null'  => ['dec_null', null, null],
            'decimal blank nullable'   => ['dec_null', '', null],
            'decimal blank not null'   => ['dec_nn', '', '0.00'],
            'decimal pads'             => ['dec_null', '12.5', '12.50'],
            'decimal rounds half up'   => ['dec_null', '1.005', '1.01'],
            'decimal rounds carry'     => ['dec_null', '9.999', '10.00'],
            'decimal negative round'   => ['dec_null', '-2.345', '-2.35'],
            'decimal negative zero'    => ['dec_null', '-0.001', '0.00'],
            'decimal int'              => ['dec_null', 7, '7.00'],
            'decimal leading dot'      => ['dec_null', '.5', '0.50'],
            'decimal no float loss'    => ['dec_big', '12345678901234567890.12345', '12345678901234567890.1235'],
            'decimal sci notation'     => ['dec_null', '1.5e3', '1500.00'],
            'decimal formatted'        => ['dec_null', '$1,234.567', '1234.57'],
            'decimal old scale'        => ['dec_old', '3.25', '3.2500000000'],
            'decimal(10,3) in type'    => ['handwritten', '1.23456', '1.235'],
            // float-ish
            'numeric'                  => ['num', '12.34', 12.34],
            'money'                    => ['money', '$19.99', 19.99],
            'money blank not null'     => ['money', '', 0.0],
            'smallmoney'               => ['smallmoney', '5', 5.0],
            'float'                    => ['flt', '1.5e2', 150.0],
            'real'                     => ['rl', 2, 2.0],
            // strings
            'char'                     => ['chr', 'abc', 'abc'],
            'varchar from int'         => ['vc', 5, '5'],
            'nvarchar'                 => ['nvc', 'héllo', 'héllo'],
            'varchar null'             => ['vc', null, null],
            'varchar blank'            => ['vc', '', ''],
            'nchar'                    => ['nchr', 'x', 'x'],
            'text'                     => ['txt', 't', 't'],
            'ntext'                    => ['ntxt', 'n', 'n'],
            'xml'                      => ['xml', '<a/>', '<a/>'],
            'json'                     => ['json', '{"a":1}', '{"a":1}'],
            'guid'                     => ['guid', '6F9619FF-8B86-D011-B42D-00C04FC964FF', '6F9619FF-8B86-D011-B42D-00C04FC964FF'],
            'guid braces'              => ['guid', '{6f9619ff-8b86-d011-b42d-00c04fc964ff}', '{6f9619ff-8b86-d011-b42d-00c04fc964ff}'],
            'guid blank'               => ['guid', '', null],
            // time / binary / other
            'time'                     => ['tm', '13:45:10.1234567', '13:45:10.1234567'],
            'binary'                   => ['bin', "\x00\x01", "\x00\x01"],
            'varbinary'                => ['vbin', 'abc', 'abc'],
            'image'                    => ['img', 'png', 'png'],
            'rowversion (timestamp)'   => ['rv', "\x00\x00\x00\x00\x00\x00\x07\xD1", "\x00\x00\x00\x00\x00\x00\x07\xD1"],
            'geometry'                 => ['geom', 'POINT (1 2)', 'POINT (1 2)'],
            'geography'                => ['geog', 'raw', 'raw'],
            'hierarchyid'              => ['hier', '/1/2/', '/1/2/'],
            'vector'                   => ['vec', '[1,2,3]', '[1,2,3]'],
            'sql_variant'              => ['variant', 42, 42],
            // special values
            'Excel #NULL!'             => ['int_null', '#NULL!', null],
            '"null" in nullable'       => ['vc', 'NULL', null],
            'primary key null'         => ['id', null, null],
        ];
    }

    #[DataProvider('conversionProvider')]
    public function testConversion(string $column, mixed $value, mixed $expected): void
    {
        self::assertSame($expected, MSSQLAllTypes::ST($column, $value));
    }

    public static function dateProvider(): array
    {
        return [
            'date'           => ['d', '2026-11-25', '2026-11-25 00:00:00'],
            'datetime'       => ['dt', '2026-11-25 13:45:10.123', '2026-11-25 13:45:10'],
            'datetime2'      => ['dt2', '2026-11-25 13:45:10.1234567', '2026-11-25 13:45:10'],
            'smalldatetime'  => ['sdt', '2026-11-25 13:45', '2026-11-25 13:45:00'],
            'datetimeoffset' => ['dto', '2026-11-25 13:45:10.1234567 -06:00', '2026-11-25 13:45:10'],
        ];
    }

    #[DataProvider('dateProvider')]
    public function testDates(string $column, string $value, string $expected): void
    {
        $date = MSSQLAllTypes::ST($column, $value);
        self::assertInstanceOf(DateTime::class, $date);
        self::assertSame($expected, $date->format('Y-m-d H:i:s'));
    }

    public function testDateBlankIsNull(): void
    {
        self::assertNull(MSSQLAllTypes::ST('dt', ''));
        self::assertNull(MSSQLAllTypes::ST('dt', null));
    }

    public function testDateObjectKeepsOffset(): void
    {
        $date = MSSQLAllTypes::ST('dto', new DateTimeImmutable('2026-11-25 08:00:00 -06:00'));
        self::assertInstanceOf(DateTime::class, $date);
        self::assertSame('-06:00', $date->format('P'));
    }

    public function testSaveFormatsKeepPrecisionAndOffset(): void
    {
        $d = new DateTime('2026-11-25 13:45:10.123456 -06:00');
        self::assertSame('2026-11-25T13:45:10.123456-06:00', MSSQLAllTypes::DateParam('dto', $d));
        self::assertSame('2026-11-25T13:45:10.123456', MSSQLAllTypes::DateParam('dt2', $d));
        self::assertSame('2026-11-25 13:45:10', MSSQLAllTypes::DateParam('dt', $d));
    }

    public static function invalidProvider(): array
    {
        return [
            'null literal in NOT NULL' => ['int_nn', 'null'],
            'text in int'              => ['int_null', 'abc'],
            'tinyint over 255'         => ['tiny', '256'],
            'tinyint negative'         => ['tiny', '-1'],
            'smallint overflow'        => ['small', '32768'],
            'int overflow'             => ['int_null', '2147483648'],
            'bigint overflow'          => ['big', '9223372036854775808'],
            'text in decimal'          => ['dec_null', 'twelve'],
            'text in money'            => ['money', 'n/a'],
            'text in float'            => ['flt', 'abc'],
            'INF in float'             => ['flt', INF],
            'bad bit'                  => ['bit_nn', 'maybe'],
            'bad guid'                 => ['guid', 'not-a-guid'],
            'bad date'                 => ['dt', 'not a date'],
            'unknown type'             => ['mystery', 'x'],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalidValuesThrow(string $column, mixed $value): void
    {
        $this->expectException(Exception::class);
        MSSQLAllTypes::ST($column, $value);
    }
}
