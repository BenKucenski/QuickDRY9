<?php
declare(strict_types=1);

namespace QuickDRY\Interfaces;

/**
 *
 */
interface ICurrentUser
{
    /**
     * @return mixed
     */
    public static function ID(): mixed;

    /**
     * @param array|null $role_ids
     * @return bool
     */
    public static function Is(?array $role_ids): bool;
}