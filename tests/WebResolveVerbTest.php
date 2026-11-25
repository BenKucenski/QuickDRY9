<?php
declare(strict_types=1);

namespace QuickDRY\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickDRY\Web\Web;

/** Tests for Web::ResolveVerb(): a GET can't be turned into a write with ?verb= */
final class WebResolveVerbTest extends TestCase
{
    public static function verbProvider(): array
    {
        return [
            'plain GET'                 => ['GET', null, 'GET'],
            'plain POST'                => ['POST', null, 'POST'],
            'HEAD is GET'               => ['HEAD', null, 'GET'],
            'POST verb=PUT'             => ['POST', 'PUT', 'PUT'],
            'POST verb=DELETE'          => ['POST', 'delete', 'DELETE'],
            'POST verb=GET (Read)'      => ['POST', 'GET', 'GET'],
            'GET verb=DELETE ignored'   => ['GET', 'DELETE', 'GET'],
            'GET verb=PUT ignored'      => ['GET', 'PUT', 'GET'],
            'GET verb=POST ignored'     => ['GET', 'POST', 'GET'],
            'GET verb=FIND allowed'     => ['GET', 'find', 'FIND'],
            'GET verb=HISTORY allowed'  => ['GET', 'HISTORY', 'HISTORY'],
            'empty verb uses method'    => ['POST', '', 'POST'],
            'non-string verb ignored'   => ['GET', ['DELETE'], 'GET'],
        ];
    }

    #[DataProvider('verbProvider')]
    public function testResolveVerb(string $method, mixed $verb, string $expected): void
    {
        $this->assertSame($expected, Web::ResolveVerb($method, $verb));
    }
}
