<?php
declare(strict_types=1);

namespace QuickDRY\Interfaces;

use QuickDRY\Connectors\SQL_Base;

/**
 *
 */
interface IChangeLog
{
    /**
     * @param SQL_Base $entity
     * @param bool $deleted
     * @return void
     */
    public static function Create(SQL_Base $entity, bool $deleted = false): void;

    //$cl = new ChangeLog();
    //$cl->entity_class = get_class($entity);
    //$cl->primary_key = $entity->PrimaryKey();
    //$cl->changes = json_encode($entity->_change_log);
    //$cl->user_id = CurrentUser::$id;
    //$cl->is_deleted = $deleted;
    //$cl->Save();

    /**
     * @param SQL_Base $entity
     * @return void
     */
    public static function Delete(SQL_Base $entity): void;

    // self::Create($entity, true);
}