<?php
declare(strict_types=1);

namespace QuickDRY\Web;

/**
 * Class BrowserOS
 */
class BrowserOS
{
    public static ?string $os = null;
    public static ?string $browser = null;
    private static ?bool $is_mobile = null;

    /**
     * @return bool
     */
    public static function IsMobile(): bool
    {
        return static::$is_mobile ?: false;
    }

    /**
     *
     */
    public static function Configure(): void
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ($ua == '') return;

        /* ==== Detect the OS ==== */

        // ---- Mobile ----

        // Android
        if (strpos($ua, 'Android')) {
            BrowserOS::$os = 'Android';
        }

        // BlackBerry
        if (strpos($ua, 'BlackBerry')) {
            BrowserOS::$os = 'BlackBerry';
        }

        // iPhone
        if (strpos($ua, 'iPhone')) {
            BrowserOS::$os = 'iPhone';
        }

        // Palm
        if (strpos($ua, 'Palm')) {
            BrowserOS::$os = 'Palm';
        }

        if (BrowserOS::$os != '')
            BrowserOS::$is_mobile = true;
        else {
            // ---- Desktop ----

            // Linux
            if (strpos($ua, 'Linux')) {
                BrowserOS::$os = 'Linux';
            }

            // Macintosh
            if (strpos($ua, 'Macintosh')) {
                BrowserOS::$os = 'Macintosh';
            }

            // Windows
            if (strpos($ua, 'Windows')) {
                BrowserOS::$os = 'Windows';
            }

            BrowserOS::$is_mobile = false;
        }

        /* ============================ */


        /* ==== Detect the UA ==== */

        // Firefox
        if (strpos($ua, 'Firefox')) {
            BrowserOS::$browser = 'Firefox';
        }
        if (strpos($ua, 'Firefox/2.0')) {
            BrowserOS::$browser = 'Firefox/2.0';
        }
        if (strpos($ua, 'Firefox/3.0')) {
            BrowserOS::$browser = 'Firefox/3.0';
        }
        if (strpos($ua, 'Firefox/3.6')) {
            BrowserOS::$browser = 'Firefox/3.6';
        }


        // Internet Explorer
        if (strpos($ua, 'MSIE')) {
            BrowserOS::$browser = 'MSIE';
        }
        if (strpos($ua, 'MSIE 7.0')) {
            BrowserOS::$browser = 'MSIE 7.0';
        }
        if (strpos($ua, 'MSIE 8.0')) {
            BrowserOS::$browser = 'MSIE 8.0';
        }


        // Opera
        $opera = preg_match('/\bOpera\b/i', $ua); // All Opera
        if ($opera != '') BrowserOS::$browser = 'Opera';

        // Safari
        if (strpos($ua, 'Safari')) {
            BrowserOS::$browser = 'Safari';
        }
        if (strpos($ua, 'Safari/419')) {
            BrowserOS::$browser = 'Safari/419';
        }
        if (strpos($ua, 'Safari/525')) {
            BrowserOS::$browser = 'Safari/525';
        }
        if (strpos($ua, 'Safari/528')) {
            BrowserOS::$browser = 'Safari/528';
        }
        if (strpos($ua, 'Safari/531')) {
            BrowserOS::$browser = 'Safari/531';
        }


        // Chrome - chrome lists safari as well so we need to check this last
        if (strpos($ua, 'Chrome')) {
            BrowserOS::$browser = 'Chrome';
        }

        /* ============================ */
    }
}
