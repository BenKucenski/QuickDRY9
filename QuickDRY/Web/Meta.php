<?php
declare(strict_types=1);

namespace QuickDRY\Web;

/**
 * Class Meta
 */
class Meta
{
    private static ?string $title = null;
    private static ?string $description = null;
    private static ?string $keywords = null;

    /**
     * @param string|null $val
     * @return null|string
     */
    public static function Title(?string $val = null): ?string
    {
        if(defined('DEMO_MODE') && DEMO_MODE) {
            self::$title .= ' - Demo -';
        }

        if (is_null($val)) {

            if(!self::$title) {
                return '';
            }
            return self::$title;
        }

        if(self::$title) {
            return self::$title . ': ' . $val;
        }

        return $val;
    }

    /**
     * @param string|null $val
     * @return string|null
     */
    public static function Description(?string $val = null): ?string
    {
        if (is_null($val)) {
            return str_replace('"', '\\"', self::$description);
        }
        self::$description = $val;
        return $val;
    }

    /**
     * @param string|null $val
     * @return string|null
     */
    public static function Keywords(?string $val = null): ?string
    {
        if (is_null($val)) {
            return str_replace('"', '\\"', self::$keywords);
        }
        self::$keywords = $val;
        return $val;
    }

    /**
     * @param string $metaTitle
     * @return void
     */
    public static function setTitle(string $metaTitle): void
    {
        self::$title = $metaTitle;
    }
}

