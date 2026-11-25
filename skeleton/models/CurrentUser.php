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

    /**
     * Replace with your own role lookup. Generated models let ROLE_ID_ADMIN see and change every
     * row, and generated /manage pages are admin-only, so never return true for roles the signed-in
     * user doesn't have.
     */
    public static function Is(?array $role_ids): bool
    {
        return false;
    }
}