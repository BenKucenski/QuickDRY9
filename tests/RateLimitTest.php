<?php
declare(strict_types=1);

namespace QuickDRY\Tests;

use PHPUnit\Framework\TestCase;
use QuickDRY\Utilities\RateLimit;

/** Tests for RateLimit */
final class RateLimitTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'quickdry_rate_limit_test_' . getmypid() . '_' . mt_rand();
        RateLimit::$Folder = $this->dir;
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
            unlink($file);
        }
        if (is_dir($this->dir)) {
            rmdir($this->dir);
        }
        RateLimit::$Folder = null;
    }

    public function testCountsHitsUpToTheLimit(): void
    {
        $this->assertFalse(RateLimit::TooMany('login', 'a@example.com', 3, 60));
        RateLimit::Hit('login', 'a@example.com', 60);
        RateLimit::Hit('login', 'A@Example.com ', 60); // keys ignore case and surrounding spaces
        $this->assertFalse(RateLimit::TooMany('login', 'a@example.com', 3, 60));
        RateLimit::Hit('login', 'a@example.com', 60);
        $this->assertTrue(RateLimit::TooMany('login', 'a@example.com', 3, 60));
    }

    public function testBucketsAndKeysAreSeparate(): void
    {
        RateLimit::Hit('login', 'a', 60);
        $this->assertTrue(RateLimit::TooMany('login', 'a', 1, 60));
        $this->assertFalse(RateLimit::TooMany('login', 'b', 1, 60));
        $this->assertFalse(RateLimit::TooMany('signup', 'a', 1, 60));
    }

    public function testClearForgetsAKey(): void
    {
        RateLimit::Hit('login', 'a', 60);
        RateLimit::Clear('login', 'a');
        $this->assertFalse(RateLimit::TooMany('login', 'a', 1, 60));
    }

    public function testUnwritableFolderNeverBlocks(): void
    {
        RateLimit::$Folder = '/proc/quickdry-cannot-write-here';
        RateLimit::Hit('login', 'a', 60);
        $this->assertFalse(RateLimit::TooMany('login', 'a', 1, 60));
    }
}
