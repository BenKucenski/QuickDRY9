<?php
declare(strict_types=1);

namespace pages\home;

use QuickDRY\Web\BasePage;

/**
 *
 */
class home extends BasePage
{
    public static ?string $Message = null;

    /**
     * @return void
     */
    public static function Get(): void
    {
        self::$Message = 'Hello World';
    }
}