<?php
declare(strict_types=1);

namespace QuickDRY\Interfaces;

use QuickDRY\Connectors\SQL_Base;

/**
 *
 */
interface IChangeLogHistory
{

    /**
     * @var IChangeLog[]|null
     */
    public ?array $changes = null {
        get;
        set;
    }

    /**
     * @param SQL_Base $object
     * @return array|null
     */
    public static function GetHistory(SQL_Base $object): ?array;
}