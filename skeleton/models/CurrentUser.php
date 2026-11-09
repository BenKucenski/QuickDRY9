<?php
declare(strict_types=1);

namespace models;

use QuickDRY\Interfaces\ICurrentUser;

/**
 *
 */
class CurrentUser implements ICurrentUser
{

    public static function ID(): int
    {
        return 0;
    }

    public static function Is(?array $role_ids): bool
    {
        return true;
    }
}