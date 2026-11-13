<?php
declare(strict_types=1);

namespace QuickDRY\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QuickDRY\Tests\Fixtures\BrokenSerializable;
use QuickDRY\Tests\Fixtures\ChildModel;
use QuickDRY\Tests\Fixtures\ModelWithHiddenProps;
use QuickDRY\Tests\Fixtures\Node;
use QuickDRY\Tests\Fixtures\Serializable;
use QuickDRY\Tests\Fixtures\Status;
use QuickDRY\Tests\Fixtures\Suit;
use RuntimeException;
use stdClass;

final class DebugRedactTest extends TestCase
{
    private const SECRETS = [
        'hunter2secret'         => '[DB_PASS]',
        'sk_live_abc123'        => '[STRIPE_KEY]',
        'sk_live_abc123_extra'  => '[STRIPE_KEY_LONG]',
        'mail.example.internal' => '[SMTP_HOST]',
    ];

    // ---- strings ------------------------------------------------------------

    public function testRedactsSecretInsideString(): void
    {
        self::assertSame(
            'connect failed: password=[DB_PASS] host=[SMTP_HOST]',
            DebugRedact('connect failed: password=hunter2secret host=mail.example.internal', self::SECRETS)
        );
    }

    public function testStringThatIsExactlyASecretIsReplaced(): void
    {
        self::assertSame('[DB_PASS]', DebugRedact('hunter2secret', self::SECRETS));
    }

    public function testStringWithoutSecretsIsUnchanged(): void
    {
        $text = 'Nothing to see here: POST /login Letter Legal landscape';
        self::assertSame($text, DebugRedact($text, self::SECRETS));
    }

    public function testEmptySecretsMapLeavesValueAlone(): void
    {
        $value = ['a' => 'hunter2secret', 'b' => [1, 2, 3]];
        self::assertSame($value, DebugRedact($value, []));
    }

    public function testLongestOverlappingSecretWins(): void
    {
        self::assertSame('key=[STRIPE_KEY_LONG]', DebugRedact('key=sk_live_abc123_extra', self::SECRETS));
        self::assertSame('key=[STRIPE_KEY]', DebugRedact('key=sk_live_abc123', self::SECRETS));
    }

    public function testRedactionIsSinglePass(): void
    {
        // A replacement label must never be re-scanned and replaced again
        $secrets = ['abcd' => '[X_abcd]'];
        self::assertSame('[X_abcd]', DebugRedact('abcd', $secrets));
    }

    // ---- scalars ------------------------------------------------------------

    public static function scalarProvider(): array
    {
        return [
            'int'   => [42],
            'float' => [3.14],
            'true'  => [true],
            'false' => [false],
            'null'  => [null],
            'empty string' => [''],
        ];
    }

    #[DataProvider('scalarProvider')]
    public function testScalarsPassThroughUnchanged(mixed $value): void
    {
        self::assertSame($value, DebugRedact($value, self::SECRETS));
    }

    // ---- arrays -------------------------------------------------------------

    public function testNestedArraysAreRedactedAndKeysPreserved(): void
    {
        $input = [
            'db' => ['user' => 'app', 'pass' => 'hunter2secret'],
            7    => 'sk_live_abc123',
            'n'  => 99,
        ];

        self::assertSame([
            'db' => ['user' => 'app', 'pass' => '[DB_PASS]'],
            7    => '[STRIPE_KEY]',
            'n'  => 99,
        ], DebugRedact($input, self::SECRETS));
    }

    public function testSecretsUsedAsArrayKeysAreRedacted(): void
    {
        $result = DebugRedact(['hunter2secret' => 'value'], self::SECRETS);

        self::assertSame(['[DB_PASS]' => 'value'], $result);
    }

    public function testServerArrayKeepsRequestMethod(): void
    {
        // Regression: the framework's own REQUEST_VERB_POST constant used to
        // turn this into "[REQUEST_VERB_POST]".
        $server = ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/report?format=JSON&size=Letter'];

        self::assertSame($server, DebugRedact($server, DebugSecretValues()));
    }

    // ---- objects ------------------------------------------------------------

    public function testClosureIsDescribed(): void
    {
        self::assertSame('[Closure]', DebugRedact(fn() => 'hunter2secret', self::SECRETS));
    }

    public function testStdClassIsFlattened(): void
    {
        $o = new stdClass();
        $o->token = 'sk_live_abc123';
        $o->count = 3;

        self::assertSame(
            ['__class' => 'stdClass', 'token' => '[STRIPE_KEY]', 'count' => 3],
            DebugRedact($o, self::SECRETS)
        );
    }

    public function testProtectedAndPrivatePropertiesAreKeptAndRedacted(): void
    {
        // Regression: get_object_vars() from outside the class only sees public
        // properties, so models (data in protected $props) came out empty.
        $model = new ModelWithHiddenProps('hunter2secret', 'mail.example.internal');

        $result = DebugRedact($model, self::SECRETS);

        self::assertSame(ModelWithHiddenProps::class, $result['__class']);
        self::assertSame(5, $result['id']);
        self::assertSame(['name' => 'Ben', 'password' => '[DB_PASS]'], $result['props']);
        self::assertSame('[SMTP_HOST]', $result['connection']);
    }

    public function testInheritedPropertiesAreIncluded(): void
    {
        $result = DebugRedact(new ChildModel('hunter2secret', 'x'), self::SECRETS);

        self::assertSame('child', $result['child_only']);
        self::assertSame('[DB_PASS]', $result['props']['password']);
        self::assertSame('x', $result['connection']);
    }

    public function testJsonSerializableUsesItsSerializedForm(): void
    {
        self::assertSame(
            ['serialized' => '[DB_PASS]'],
            DebugRedact(new Serializable('hunter2secret'), self::SECRETS)
        );
    }

    public function testJsonSerializableThatThrowsFallsBackToProperties(): void
    {
        $result = DebugRedact(new BrokenSerializable(), self::SECRETS);

        self::assertSame(BrokenSerializable::class, $result['__class']);
        self::assertSame('still here', $result['visible']);
    }

    public function testEnumsAreShownByCaseName(): void
    {
        self::assertSame(Suit::class . '::Hearts', DebugRedact(Suit::Hearts, self::SECRETS));
        self::assertSame(Status::class . '::Active', DebugRedact(Status::Active, self::SECRETS));
    }

    public function testDateTimeKeepsItsValue(): void
    {
        $result = DebugRedact(new \DateTimeImmutable('2026-11-13 08:45:00', new \DateTimeZone('UTC')), self::SECRETS);

        self::assertSame(\DateTimeImmutable::class, $result['__class']);
        self::assertSame('2026-11-13 08:45:00.000000', $result['date']);
        self::assertSame('UTC', $result['timezone']);
    }

    // ---- exceptions ---------------------------------------------------------

    public function testExceptionKeepsMessageCodeFileAndLine(): void
    {
        // Regression: exceptions have no public properties, so the whole
        // value used to collapse to ['__class' => 'RuntimeException'].
        $line = __LINE__ + 1;
        $e = new RuntimeException('login failed for password hunter2secret', 42);

        $result = DebugRedact($e, self::SECRETS);

        self::assertSame(RuntimeException::class, $result['__class']);
        self::assertSame('login failed for password [DB_PASS]', $result['message']);
        self::assertSame(42, $result['code']);
        self::assertSame(__FILE__, $result['file']);
        self::assertSame($line, $result['line']);
        self::assertIsArray($result['trace']);
        self::assertNull($result['previous']);
    }

    public function testExceptionChainIsFollowed(): void
    {
        $inner = new \LogicException('inner uses sk_live_abc123');
        $outer = new RuntimeException('outer', 0, $inner);

        $result = DebugRedact($outer, self::SECRETS);

        self::assertSame(\LogicException::class, $result['previous']['__class']);
        self::assertSame('inner uses [STRIPE_KEY]', $result['previous']['message']);
    }

    public function testExceptionTraceArgumentsAreRedacted(): void
    {
        $e = $this->throwWith('hunter2secret');

        $json = json_encode(DebugRedact($e, self::SECRETS));

        self::assertIsString($json);
        self::assertStringNotContainsString('hunter2secret', $json);
    }

    private function throwWith(string $password): RuntimeException
    {
        return new RuntimeException('failed');
    }

    // ---- depth / recursion / resources -------------------------------------

    public function testDeepNestingIsCut(): void
    {
        $deep = 'leaf';
        for ($i = 0; $i < 15; $i++) {
            $deep = [$deep];
        }

        $json = json_encode(DebugRedact($deep, self::SECRETS));

        self::assertStringContainsString('[max depth]', $json);
        self::assertStringNotContainsString('leaf', $json);
    }

    public function testNestingWithinLimitIsKept(): void
    {
        $value = ['a' => ['b' => ['c' => ['d' => 'hunter2secret']]]];

        self::assertSame(
            ['a' => ['b' => ['c' => ['d' => '[DB_PASS]']]]],
            DebugRedact($value, self::SECRETS)
        );
    }

    public function testCircularObjectGraphTerminates(): void
    {
        $a = new Node();
        $b = new Node();
        $a->next = $b;
        $b->next = $a;

        $json = json_encode(DebugRedact($a, self::SECRETS));

        self::assertIsString($json);
        self::assertStringContainsString('[max depth]', $json);
    }

    public function testResourceIsDescribed(): void
    {
        $fh = fopen('php://memory', 'r');
        try {
            self::assertSame('[resource (stream)]', DebugRedact($fh, self::SECRETS));
        } finally {
            fclose($fh);
        }
    }

    // ---- what Debug() actually does with the result -------------------------

    public function testDebugPayloadIsJsonEncodableAndLeakFree(): void
    {
        $fh = fopen('php://memory', 'r');
        $payload = [
            'Exception',
            [
                new RuntimeException('bad hunter2secret'),
                new ModelWithHiddenProps('hunter2secret', 'mail.example.internal'),
                fn() => null,
                $fh,
                Suit::Spades,
            ],
        ];

        $json = json_encode(DebugRedact($payload, self::SECRETS), JSON_THROW_ON_ERROR);
        fclose($fh);

        self::assertStringNotContainsString('hunter2secret', $json);
        self::assertStringNotContainsString('mail.example.internal', $json);
        self::assertStringContainsString('bad [DB_PASS]', $json);
        self::assertStringContainsString('"name":"Ben"', $json);
    }
}
