<?php
declare(strict_types=1);

namespace QuickDRY\Tests;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

// Constants can't be undefined, so every test gets its own process.
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class DebugRedactSkipTest extends TestCase
{
    /**
     * DebugSecretValues() without the constants PHPUnit defines in the child
     * process (PHPUNIT_COMPOSER_INSTALL etc.), which aren't the app's.
     */
    private static function appSecrets(): array
    {
        return array_filter(
            DebugSecretValues(),
            static fn(string $label): bool => !str_starts_with($label, '[PHPUNIT_'),
        );
    }

    public function testRequestConstantsAreNotRedacted(): void
    {
        define('DB_PASS', 'hunter2secret');
        define('BASE_URL', 'https://example.test');
        define('CURRENT_PAGE', '/signin');
        define('CURRENT_PAGE_NAME', 'signin');

        $log = [
            'PageClass' => 'pages\\signin\\signin',
            'ViewFile'  => 'pages/signin/signin.html.php',
            'Referer'   => 'https://example.test/signin',
            'Error'     => 'login failed for hunter2secret',
        ];

        self::assertSame([
            'PageClass' => 'pages\\signin\\signin',
            'ViewFile'  => 'pages/signin/signin.html.php',
            'Referer'   => 'https://example.test/signin',
            'Error'     => 'login failed for [DB_PASS]',
        ], DebugRedact($log, DebugSecretValues()));
    }

    public function testSkipListByName(): void
    {
        define('DB_PASS', 'hunter2secret');
        define('SESSION_NAME', 'MyAppSession');
        define('DEBUG_REDACT_SKIP', ['SESSION_NAME']);

        $secrets = DebugSecretValues();

        self::assertArrayNotHasKey('MyAppSession', $secrets);
        self::assertSame('[DB_PASS]', $secrets['hunter2secret']);
    }

    public function testSkipListByPrefix(): void
    {
        define('DB_PASS', 'hunter2secret');
        define('APP_TITLE', 'My Application');
        define('APP_THEME', 'darkmode');
        define('DEBUG_REDACT_SKIP', ['APP_*']);

        self::assertSame(['hunter2secret' => '[DB_PASS]'], self::appSecrets());
    }

    public function testSkipListAsCommaSeparatedString(): void
    {
        define('DB_PASS', 'hunter2secret');
        define('APP_TITLE', 'My Application');
        define('SESSION_NAME', 'MyAppSession');
        define('DEBUG_REDACT_SKIP', 'SESSION_NAME, APP_*');

        self::assertSame(['hunter2secret' => '[DB_PASS]'], self::appSecrets());
    }

    public function testWildcardTurnsRedactionOff(): void
    {
        define('DB_PASS', 'hunter2secret');
        define('DEBUG_REDACT_SKIP', ['*']);

        self::assertSame([], DebugSecretValues());
    }
}
