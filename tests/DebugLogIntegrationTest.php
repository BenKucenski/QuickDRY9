<?php
declare(strict_types=1);

namespace QuickDRY\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Runs the real Debug() in a child PHP process (it calls exit()) and checks
 * the log file it writes to DATA_FOLDER/logs.
 */
final class DebugLogIntegrationTest extends TestCase
{
    private string $dataFolder;

    protected function setUp(): void
    {
        $this->dataFolder = sys_get_temp_dir() . '/quickdry-test-' . bin2hex(random_bytes(6));
        mkdir($this->dataFolder);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dataFolder . '/logs/*') ?: [] as $f) {
            unlink($f);
        }
        @rmdir($this->dataFolder . '/logs');
        @rmdir($this->dataFolder);
    }

    private function runDebugScript(string $body): array
    {
        $bootstrap = var_export(__DIR__ . '/bootstrap.php', true);
        $folder = var_export($this->dataFolder, true);

        $script = <<<PHP
        <?php
        declare(strict_types=1);
        require {$bootstrap};
        define('DATA_FOLDER', {$folder});
        define('CONST_OUTPUT_ERRORS', false);
        define('DB_PASSWORD', 'hunter2secret');
        \$_SERVER['REQUEST_METHOD'] = 'POST';
        {$body}
        PHP;

        $file = $this->dataFolder . '/run.php';
        file_put_contents($file, $script);

        $output = [];
        exec(escapeshellarg(PHP_BINARY) . ' -d zend.exception_ignore_args=0 ' . escapeshellarg($file) . ' 2>&1', $output);
        unlink($file);

        $logs = glob($this->dataFolder . '/logs/*.txt') ?: [];
        self::assertCount(1, $logs, "Debug() should write exactly one log. Output:\n" . implode("\n", $output));

        $raw = file_get_contents($logs[0]);
        $log = json_decode($raw, true);
        self::assertIsArray($log, 'log must be valid JSON');

        return [$raw, $log, implode("\n", $output)];
    }

    public function testExceptionLogContainsDetailsButNotSecrets(): void
    {
        [$raw, $log, $output] = $this->runDebugScript(<<<'PHP'
            function connect(string $password) {
                throw new RuntimeException('Access denied using password ' . $password);
            }
            try {
                connect(DB_PASSWORD);
            } catch (Throwable $e) {
                Exception($e);
            }
            PHP);

        self::assertStringStartsWith('<p>An Error Occurred: ', $output);
        self::assertStringNotContainsString('hunter2secret', $raw);

        // Debug('Exception', $args) -> data = ['Exception', [$e]]
        $e = $log['data'][1][0];
        self::assertSame('RuntimeException', $e['__class']);
        self::assertSame('Access denied using password [DB_PASSWORD]', $e['message']);
        self::assertGreaterThan(0, $e['line']);
        self::assertSame('connect', $e['trace'][0]['function']);
        self::assertSame(['[DB_PASSWORD]'], $e['trace'][0]['args']);

        self::assertSame('POST', $log['server']['REQUEST_METHOD']);
        self::assertNotEmpty($log['backtrace']);
    }
}
