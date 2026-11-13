<?php
declare(strict_types=1);

namespace QuickDRY\Tests\Fixtures;

use JsonSerializable;

/** Mimics a SQL_Base model: data lives in protected/private properties. */
class ModelWithHiddenProps
{
    public int $id = 5;
    protected array $props = ['name' => 'Ben', 'password' => 'unset'];
    private string $connection = 'unset';

    public function __construct(string $password, string $connection)
    {
        $this->props['password'] = $password;
        $this->connection = $connection;
    }
}

class ChildModel extends ModelWithHiddenProps
{
    protected string $child_only = 'child';
}

class Serializable implements JsonSerializable
{
    public function __construct(private string $value)
    {
    }

    public function jsonSerialize(): array
    {
        return ['serialized' => $this->value];
    }
}

class BrokenSerializable implements JsonSerializable
{
    public string $visible = 'still here';

    public function jsonSerialize(): mixed
    {
        throw new \RuntimeException('cannot serialize');
    }
}

class Node
{
    public ?Node $next = null;
    public string $label = 'node';
}

enum Suit: string
{
    case Hearts = 'H';
    case Spades = 'S';
}

enum Status
{
    case Active;
    case Inactive;
}

class Typed
{
    public int $int;
    public ?int $nullableInt;
    public bool $bool;
    public float $float;
    public string $string;
    public array $array;
    public ?\DateTimeImmutable $date;
    public Suit $suit;
    public ?Status $status;
    public int|string $intOrString;
    public $untyped;
}
