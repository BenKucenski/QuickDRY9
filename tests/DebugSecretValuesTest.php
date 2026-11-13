<?php
declare(strict_types=1);

namespace QuickDRY\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Constants are process-global, so every constant defined here uses a
 * TEST_SECRET_ prefix and is only defined once.
 */
final class DebugSecretValuesTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $defs = [
            'TEST_SECRET_DB_PASS' => 'hunter2secret',
            'TEST_SECRET_SHORT'   => 'abc',
            'TEST_SECRET_INT'     => 12345,
            'TEST_SECRET_BOOL'    => true,
        ];
        foreach ($defs as $name => $value) {
            if (!defined($name)) {
                define($name, $value);
            }
        }
    }

    public function testUserStringConstantIsMappedToItsName(): void
    {
        $secrets = DebugSecretValues();

        self::assertSame('[TEST_SECRET_DB_PASS]', $secrets['hunter2secret'] ?? null);
    }

    public function testShortStringsAndNonStringsAreSkipped(): void
    {
        $secrets = DebugSecretValues();

        self::assertNotContains('[TEST_SECRET_SHORT]', $secrets);
        self::assertNotContains('[TEST_SECRET_INT]', $secrets);
        self::assertNotContains('[TEST_SECRET_BOOL]', $secrets);
    }

    public function testFrameworkConstantsAreNotTreatedAsSecrets(): void
    {
        // Regression: QuickDRY's own constants (REQUEST_VERB_POST = 'POST',
        // PDF_PAGE_SIZE_LETTER = 'Letter', ...) were being "redacted" all over
        // the debug output.
        $secrets = DebugSecretValues();

        foreach (['POST', 'DELETE', 'JSON', 'DOCX', 'Letter', 'Legal', 'landscape', 'portrait', 'Executive'] as $value) {
            self::assertArrayNotHasKey($value, $secrets, "'$value' should not be redacted");
        }
        self::assertNotContains('[QUICKDRY_PROJECT_ROOT]', $secrets);
    }

    public function testSkipListIsHonoured(): void
    {
        if (!defined('DATA_FOLDER')) {
            define('DATA_FOLDER', '/tmp/quickdry-test-data');
        }

        self::assertNotContains('[DATA_FOLDER]', DebugSecretValues());
    }

    public function testEndToEndRedactionWithDefinedConstants(): void
    {
        $out = DebugRedact(
            ['REQUEST_METHOD' => 'POST', 'error' => 'Access denied using password hunter2secret'],
            DebugSecretValues()
        );

        self::assertSame('POST', $out['REQUEST_METHOD']);
        self::assertSame('Access denied using password [TEST_SECRET_DB_PASS]', $out['error']);
    }
}
