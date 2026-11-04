<?php
declare(strict_types=1);

namespace QuickDRY\Web;

use QuickDRY\Utilities\strongType;

/**
 *
 */
class ElementID extends strongType
{
    public ?string $id = null;
    public ?string $name = null;

    /**
     * @param string|null $id
     * @param string|null $name
     */
    public function __construct(?string $id = null, ?string $name = null)
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