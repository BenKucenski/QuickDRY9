<?php
declare(strict_types=1);

namespace models;

use QuickDRY\Connectors\SQL_Base;
use QuickDRY\Interfaces\IChangeLogHistory;

/**
 *
 */
class ChangeLogHistory implements IChangeLogHistory
{

    /**
     * @param SQL_Base $object
     * @return array|null
     */
    public static function GetHistory(SQL_Base $object): ?self
    {
        $t = new self();
        $t->changes = null;
        return $t;
    }

    public array|null $changes {
        get {
            return $this->changes;
        }
        set {
            $this->changes = $value;
        }
    }
}