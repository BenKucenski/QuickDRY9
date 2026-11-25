<?php
declare(strict_types=1);

namespace QuickDRY\Tests;

use PHPUnit\Framework\TestCase;
use QuickDRY\Web\Csrf;

/** Tests for Csrf tokens and which requests need one */
final class CsrfTest extends TestCase
{
    private array $savedServer;
    private array $savedPost;
    private ?array $savedSession;

    protected function setUp(): void
    {
        $this->savedServer = $_SERVER;
        $this->savedPost = $_POST;
        $this->savedSession = $_SESSION ?? null;
        $_SESSION = [];
        $_POST = [];
        unset($_SERVER[Csrf::HEADER]);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->savedServer;
        $_POST = $this->savedPost;
        if ($this->savedSession === null) {
            unset($_SESSION);
        } else {
            $_SESSION = $this->savedSession;
        }
    }

    public function testTokenIsStablePerSession(): void
    {
        $token = Csrf::Token();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        $this->assertSame($token, Csrf::Token());

        $_SESSION = [];
        $this->assertNotSame($token, Csrf::Token());
    }

    public function testMetaTagAndInputCarryTheToken(): void
    {
        $token = Csrf::Token();
        $this->assertSame('<meta name="csrf-token" content="' . $token . '">', Csrf::MetaTag());
        $this->assertStringContainsString('name="csrf_token" value="' . $token . '"', Csrf::HiddenInput());
    }

    public function testWhichRequestsNeedAToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertFalse(Csrf::IsStateChanging('GET'));
        $this->assertFalse(Csrf::IsStateChanging('FIND'));
        $this->assertTrue(Csrf::IsStateChanging('DELETE'));

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertTrue(Csrf::IsStateChanging('POST'));
        $this->assertTrue(Csrf::IsStateChanging('GET')); // QuickDRY.Read is a POST too
    }

    public function testValidAcceptsHeaderOrField(): void
    {
        $token = Csrf::Token();
        $this->assertFalse(Csrf::Valid());

        $_SERVER[Csrf::HEADER] = $token;
        $this->assertTrue(Csrf::Valid());

        unset($_SERVER[Csrf::HEADER]);
        $_POST[Csrf::FIELD] = $token;
        $this->assertTrue(Csrf::Valid());
    }

    public function testValidRejectsWrongOrMissingToken(): void
    {
        Csrf::Token();
        $_POST[Csrf::FIELD] = str_repeat('0', 64);
        $this->assertFalse(Csrf::Valid());

        $_POST[Csrf::FIELD] = ['array'];
        $this->assertFalse(Csrf::Valid());

        $_SESSION = [];
        $_POST[Csrf::FIELD] = '';
        $this->assertFalse(Csrf::Valid()); // no session token: nothing matches, not even ''
    }
}
