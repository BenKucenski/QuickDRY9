<?php

namespace Bkucenski\Quickdry\Web;

use Bkucenski\Quickdry\Utilities\strongType;

/**
 *
 */
class ElementID
{
    public ?string $id = null;
    public ?string $name = null;

    /**
     * @param string|null $id
     * @param string|null $name
     */
    public function __construct(string $id = null, string $name = null)
    {
        $this->id = $id ?? $name;
        $this->name = $name ?? $id;
    }

    /**
     * @param array $row
     * @return ElementID
     */
    public static function FromArray(array $row): ElementID
    {
        return new self($row['id'] ?? null, $row['name'] ?? null);
    }
}