<?php
namespace pages\home;

use QuickDRY\Web\BasePage;

class home extends BasePage
{
    public static ?string $Message = null;

    public static function Get(): void
    {
        self::$Message = 'Hello World';
    }
}