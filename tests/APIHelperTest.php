<?php
declare(strict_types=1);

namespace QuickDRY\Tests;

use PHPUnit\Framework\TestCase;
use QuickDRY\Connectors\MSSQL;
use QuickDRY\Utilities\APIHelper;

/**
 * APIHelper's Apply* helpers must write placeholders with the prefix getSQL()
 * used, because each escaper fills in only its own: MySQL::EscapeQuery()
 * ":name", MSSQL::EscapeQuery() "@name". They used to always write "@", which
 * on MySQL left the IN list as empty user variables (no error, no rows).
 */
final class APIHelperTest extends TestCase
{
    private ?string $saved_placeholder;
    private array $files = [];

    protected function setUp(): void
    {
        $this->saved_placeholder = APIHelper::$default_placeholder;
        APIHelper::$_CACHE = [];
    }

    protected function tearDown(): void
    {
        APIHelper::$default_placeholder = $this->saved_placeholder;
        APIHelper::$_CACHE = [];
        foreach ($this->files as $f) {
            @unlink($f);
        }
    }

    private function sqlFile(string $sql): string
    {
        $file = tempnam(sys_get_temp_dir(), 'qd_sql_');
        file_put_contents($file, $sql);
        $this->files[] = $file;
        return $file;
    }

    /**
     * Every placeholder with the given prefix in $sql, by name.
     *
     * @return string[]
     */
    private static function placeholders(string $sql, string $prefix): array
    {
        preg_match_all('/' . preg_quote($prefix, '/') . '([A-Za-z_][A-Za-z0-9_]*)/', $sql, $m);
        return $m[1];
    }

    // ---- MySQL (default ":") -------------------------------------------------

    public function testApplyArrayUsesTheColonPrefixGetSQLLeftForMySQL(): void
    {
        $sql = APIHelper::getSQL($this->sqlFile("-- ids\nSELECT * FROM t WHERE id IN ('--ids--') AND kind = @kind"));
        $params = ['kind' => 'a'];

        APIHelper::ApplyArray('ids', [10, 20, 30], $sql, $params);

        self::assertSame('SELECT * FROM t WHERE id IN (:ids0, :ids1, :ids2) AND kind = :kind', $sql);
        self::assertSame(['kind' => 'a', 'ids0' => 10, 'ids1' => 20, 'ids2' => 30], $params);
        self::assertStringNotContainsString('@', $sql, 'an @ placeholder is never filled in by MySQL::EscapeQuery');
    }

    public function testEveryPlaceholderHasAParameter(): void
    {
        $sql = APIHelper::getSQL($this->sqlFile("SELECT * FROM t WHERE id IN ('--ids--') AND x IN ('--xs--')"));
        $params = [];

        // array_unique/array_filter leave gaps in the keys; placeholders are numbered 0..n-1
        APIHelper::ApplyArray('ids', array_unique([5, 5, 7, 9]), $sql, $params);
        APIHelper::ApplyArray('xs', ['k' => 'one', 'm' => 'two'], $sql, $params);

        foreach (self::placeholders($sql, ':') as $name) {
            self::assertArrayHasKey($name, $params);
        }
        self::assertSame([5, 7, 9], [$params['ids0'], $params['ids1'], $params['ids2']]);
        self::assertSame(['one', 'two'], [$params['xs0'], $params['xs1']]);
    }

    public function testEmptyArrayLeavesTheMarkerSoTheInListMatchesNothing(): void
    {
        $sql = "SELECT * FROM t WHERE id IN ('--ids--')";
        $params = [];

        APIHelper::ApplyArray('ids', [], $sql, $params);

        self::assertSame("SELECT * FROM t WHERE id IN ('--ids--')", $sql);
        self::assertSame([], $params);
    }

    public function testApplyValuesUsesTheDefaultPrefix(): void
    {
        $sql = "INSERT INTO t (v) VALUES '--vals--'";
        $params = [];

        APIHelper::ApplyValues('vals', ['a', 'b'], $sql, $params);

        self::assertSame('INSERT INTO t (v) VALUES (:vals0),(:vals1)', $sql);
        self::assertSame(['vals0' => 'a', 'vals1' => 'b'], $params);
    }

    public function testApplyValuesArrayNamesCannotCollide(): void
    {
        // 12 rows of 12 columns: without a separator, row 1 col 11 and row 11 col 1 were both "rows111"
        $rows = [];
        for ($r = 0; $r < 12; $r++) {
            $rows[] = array_map(static fn(int $c) => "r{$r}c{$c}", range(0, 11));
        }
        $sql = "INSERT INTO t VALUES '--rows--'";
        $params = [];

        APIHelper::ApplyValuesArray('rows', $rows, $sql, $params);

        self::assertCount(144, $params);
        self::assertSame('r1c11', $params['rows1_11']);
        self::assertSame('r11c1', $params['rows11_1']);
        self::assertStringStartsWith('INSERT INTO t VALUES (:rows0_0,:rows0_1,', $sql);
        self::assertSame(12, substr_count($sql, '('));
        foreach (self::placeholders($sql, ':') as $name) {
            self::assertArrayHasKey($name, $params);
        }
    }

    // ---- SQL Server (default "@") ------------------------------------------

    public function testSqlServerKeepsTheAtPrefixAndEscapesEndToEnd(): void
    {
        APIHelper::$default_placeholder = '@';
        $sql = APIHelper::getSQL($this->sqlFile("SELECT * FROM t WHERE name IN ('--names--') AND kind = @kind"));
        $params = ['kind' => 'a'];

        APIHelper::ApplyArray('names', ["O'Brien", 'Smith'], $sql, $params);

        self::assertSame('SELECT * FROM t WHERE name IN (@names0, @names1) AND kind = @kind', $sql);
        $escaped = MSSQL::EscapeQuery($sql, $params);
        self::assertStringNotContainsString('@names', $escaped);
        self::assertStringContainsString("O''Brien", $escaped);
    }

    public function testExplicitPrefixMatchesAGetSQLOverride(): void
    {
        // default stays ":", but this file was loaded for SQL Server with getSQL($file, '@')
        $sql = APIHelper::getSQL($this->sqlFile("SELECT * FROM t WHERE id IN ('--ids--') AND kind = @kind"), '@');
        $params = ['kind' => 'a'];

        APIHelper::ApplyArray('ids', [1, 2], $sql, $params, '@');

        self::assertSame('SELECT * FROM t WHERE id IN (@ids0, @ids1) AND kind = @kind', $sql);
        self::assertStringNotContainsString('@ids', MSSQL::EscapeQuery($sql, $params));
    }
}
