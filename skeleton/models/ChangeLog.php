<?php
declare(strict_types=1);

namespace models;

use QuickDRY\Connectors\SQL_Base;
use QuickDRY\Interfaces\IChangeLog;

/**
 *
 */
class ChangeLog implements IChangeLog
{

    /**
     * @param SQL_Base $entity
     * @param bool $deleted
     * @return void
     */
    public static function Create(SQL_Base $entity, bool $deleted = false): void
    {
        // TODO: Implement Create() method.
    }

    /**
     * @param SQL_Base $entity
     * @return void
     */
    public static function Delete(SQL_Base $entity): void
    {
        // TODO: Implement Delete() method.
    }
}