<?php
declare(strict_types=1);

namespace QuickDRY\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickDRY\Utilities\Navigation;
use QuickDRY\Web\Web;

/** Tests for Web::AccessDecision() and Navigation::Allows() */
final class WebAccessTest extends TestCase
{
    private static function web(string $page, bool $signed_in, ?array $allowed = null, ?string $master_page = null): Web
    {
        $web = new Web();
        $web->CurrentPage = $page;
        $web->SetSignedInCheck(fn() => $signed_in);
        $web->SetSecureMasterPages(['user.php']);
        $web->MasterPage = $master_page;
        if ($allowed !== null) {
            $nav = new Navigation();
            $nav->Combine($allowed);
            $web->SetNavigation($nav);
        }
        return $web;
    }

    public function testNavigationAllowsIgnoresCaseSlashQueryAndDashes(): void
    {
        $nav = new Navigation();
        $nav->Combine(['/manage/stanworth/color', '/lab-data/icp']);
        $this->assertTrue($nav->Allows('/manage/stanworth/Color'));
        $this->assertTrue($nav->Allows('/MANAGE/stanworth/color/?page=2'));
        $this->assertTrue($nav->Allows('/lab_data/icp'));
        $this->assertFalse($nav->Allows('/manage/stanworth/colors'));
        $this->assertFalse($nav->Allows('/manage/stanworth'));
        $this->assertTrue($nav->CheckPermissions('/Manage/Stanworth/Color', true));
    }

    public function testPathStartsWith(): void
    {
        $this->assertTrue(Web::PathStartsWith('/json/db/Invoice', ['/json']));
        $this->assertTrue(Web::PathStartsWith('/JSON', ['/json']));
        $this->assertTrue(Web::PathStartsWith('/Manage/db/User/', ['/json', '/manage']));
        $this->assertFalse(Web::PathStartsWith('/jsonfeed', ['/json']));
        $this->assertFalse(Web::PathStartsWith('/reports/manage', ['/manage']));
    }

    public static function beforeInitProvider(): array
    {
        return [
            // page, signed in, navigation (null = none), expected
            'anon json, no nav'                 => ['/json/db/Invoice', false, null, 'signin'],
            'anon manage, no nav'               => ['/manage/db/User', false, null, 'signin'],
            'anon manage, other case'           => ['/Manage/DB/User', false, null, 'signin'],
            'user json, no nav'                 => ['/json/db/Invoice', true, null, 'allow'],
            'user manage, no nav'               => ['/manage/db/User', true, null, 'allow'],
            'anon ordinary page'                => ['/home', false, null, 'allow'],
            'anon json allowed by nav'          => ['/json/db/Invoice', false, ['/json/db/invoice'], 'allow'],
            'anon json not allowed by nav'      => ['/json/db/User', false, ['/json/db/invoice'], 'signin'],
            'user json not in nav'              => ['/json/db/User', true, ['/home'], 'allow'],
            'user manage not in nav'            => ['/manage/db/User', true, ['/home'], 'deny'],
            'anon manage not in nav'            => ['/manage/db/User', false, ['/home'], 'signin'],
            'admin manage in nav'               => ['/manage/db/User', true, ['/manage/db/user'], 'allow'],
        ];
    }

    #[DataProvider('beforeInitProvider')]
    public function testBeforeInit(string $page, bool $signed_in, ?array $allowed, string $expected): void
    {
        $this->assertSame($expected, self::web($page, $signed_in, $allowed)->AccessDecision(false));
    }

    public static function afterInitProvider(): array
    {
        return [
            // page, signed in, navigation, master page, expected
            'public master page, anon'          => ['/about', false, null, 'default.php', 'allow'],
            'no master page, anon'              => ['/ajax/thing', false, null, null, 'allow'],
            'secure master page, anon'          => ['/clock_in', false, null, 'user.php', 'signin'],
            'secure master page, user, no nav'  => ['/clock_in', true, null, 'user.php', 'allow'],
            'secure, user, listed'              => ['/clock_in', true, ['/clock_in'], 'user.php', 'allow'],
            'secure, user, not listed'          => ['/reports/secret', true, ['/clock_in'], 'user.php', 'deny'],
            'secure, anon, listed for anon'     => ['/signup', false, ['/signup'], 'user.php', 'allow'],
        ];
    }

    #[DataProvider('afterInitProvider')]
    public function testAfterInit(string $page, bool $signed_in, ?array $allowed, ?string $master_page, string $expected): void
    {
        $this->assertSame($expected, self::web($page, $signed_in, $allowed, $master_page)->AccessDecision(true));
    }

    public function testPublicPathsSkipEverything(): void
    {
        $web = self::web('/api/v1/orders', false, ['/home'], 'user.php');
        $web->PublicPaths = ['/api'];
        $this->assertSame('allow', $web->AccessDecision(false));
        $this->assertSame('allow', $web->AccessDecision(true));
    }
}
