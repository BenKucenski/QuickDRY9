<?php
declare(strict_types=1);

namespace QuickDRY\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickDRY\Web\Server;

/** Tests for Server::REMOTE_ADDR() and its IP helpers */
final class ServerRemoteAddrTest extends TestCase
{
    private array $savedServer;
    private array $savedProxies;

    protected function setUp(): void
    {
        $this->savedServer = $_SERVER;
        $this->savedProxies = Server::$TrustedProxies;
        unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_CF_CONNECTING_IP']);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->savedServer;
        Server::$TrustedProxies = $this->savedProxies;
    }

    // ---- NormalizeIP --------------------------------------------------------

    public static function normalizeProvider(): array
    {
        return [
            'ipv4'                 => ['203.0.113.7', '203.0.113.7'],
            'ipv4 with port'       => ['203.0.113.7:443', '203.0.113.7'],
            'ipv6 loopback'        => ['::1', '::1'],
            'ipv6 uppercase long'  => ['2001:0DB8:0000:0000:0000:0000:0000:0001', '2001:db8::1'],
            'ipv6 bracketed'       => ['[2001:db8::1]', '2001:db8::1'],
            'ipv6 bracketed+port'  => ['[2001:db8::1]:8080', '2001:db8::1'],
            'ipv6 zone id'         => ['fe80::1%eth0', 'fe80::1'],
            'ipv4-mapped ipv6'     => ['::ffff:198.51.100.9', '198.51.100.9'],
            'whitespace'           => ['  2001:db8::5 ', '2001:db8::5'],
            'garbage'              => ['not-an-ip', null],
            'empty'                => ['', null],
            'unknown'              => ['unknown', null],
        ];
    }

    #[DataProvider('normalizeProvider')]
    public function testNormalizeIP(string $in, ?string $expected): void
    {
        self::assertSame($expected, Server::NormalizeIP($in));
    }

    // ---- IPInRange ----------------------------------------------------------

    public function testIPInRange(): void
    {
        self::assertTrue(Server::IPInRange('127.0.0.1', '127.0.0.0/8'));
        self::assertTrue(Server::IPInRange('::1', '::1/128'));
        self::assertTrue(Server::IPInRange('::1', '::1'));
        self::assertTrue(Server::IPInRange('10.1.2.3', '10.0.0.0/8'));
        self::assertTrue(Server::IPInRange('2001:db8:1::5', '2001:db8::/32'));
        self::assertTrue(Server::IPInRange('172.31.0.1', '172.16.0.0/12'));
        self::assertFalse(Server::IPInRange('172.32.0.1', '172.16.0.0/12'));
        self::assertFalse(Server::IPInRange('2001:db9::1', '2001:db8::/32'));
        self::assertFalse(Server::IPInRange('127.0.0.1', '::1/128'));   // family mismatch
        self::assertFalse(Server::IPInRange('1.2.3.4', 'nonsense/8'));
    }

    // ---- REMOTE_ADDR --------------------------------------------------------

    public function testDirectIPv6ClientIsReturnedAsIs(): void
    {
        $_SERVER['REMOTE_ADDR'] = '2600:1700:ABCD::1';
        self::assertSame('2600:1700:abcd::1', Server::REMOTE_ADDR());
    }

    public function testForwardedHeadersIgnoredFromUntrustedPeer(): void
    {
        // A client talking straight to the server can't pick its own address.
        $_SERVER['REMOTE_ADDR'] = '203.0.113.50';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '127.0.0.1';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '::1';
        self::assertSame('203.0.113.50', Server::REMOTE_ADDR());
    }

    public function testCloudflareTunnelUsesCfConnectingIp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '::1'; // cloudflared on the same machine
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '2a02:26f0:0:0::1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2a02:26f0::1';
        self::assertSame('2a02:26f0::1', Server::REMOTE_ADDR());
    }

    public function testSpoofedLeftmostForwardedForIsNotTrusted(): void
    {
        // Client sent "X-Forwarded-For: 127.0.0.1"; the proxy appended the real address.
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '127.0.0.1, 198.51.100.23';
        self::assertSame('198.51.100.23', Server::REMOTE_ADDR());

        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1, 2001:db8::77';
        self::assertSame('2001:db8::77', Server::REMOTE_ADDR());
    }

    public function testForwardedForSkipsTrustedHops(): void
    {
        Server::$TrustedProxies = ['127.0.0.0/8', '::1/128', '10.0.0.0/8'];
        $_SERVER['REMOTE_ADDR'] = '::1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.1, 2001:db8::9, 10.0.0.5';
        self::assertSame('2001:db8::9', Server::REMOTE_ADDR());
    }

    public function testInvalidCfHeaderFallsBackToForwardedFor(): void
    {
        $_SERVER['REMOTE_ADDR'] = '::1';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = 'garbage';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.4';
        self::assertSame('198.51.100.4', Server::REMOTE_ADDR());
    }

    public function testTrustedPeerWithNoHeadersReturnsPeer(): void
    {
        $_SERVER['REMOTE_ADDR'] = '::1';
        self::assertSame('::1', Server::REMOTE_ADDR());
    }

    public function testMissingRemoteAddr(): void
    {
        self::assertNull(Server::REMOTE_ADDR());
    }
}
