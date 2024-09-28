<?php

namespace Bkucenski\Quickdry\Utilities;

use Exception;
use ReflectionClass;

/**
 * Class Debug
 */
class Debug
{
    /**
     * @param $class_name
     */
    public static function HaltStatic($class_name): void
    {
        try {
            $reflection = new ReflectionClass($class_name);
            self::Halt($reflection->getStaticProperties());
        } catch (Exception $ex) {
            self::Halt($ex);
        }
    }

    /**
     * @param $class_name
     */
    public static function CleanHaltStatic($class_name): void
    {
        try {
            $reflection = new ReflectionClass($class_name);
            self::CleanHalt($reflection->getStaticProperties());
        } catch (Exception $ex) {
            self::Halt($ex);
        }
    }

    /**
     * @param $var
     * @param null $message
     */
    public static function CleanHalt($var, $message = null): void
    {
        static::_Debug($var, $message, true, true, false);

    }

    /**
     * @param $var
     * @param null $message
     */
    public static function Halt($var, $message = null): void
    {
        static::_Debug($var, $message, true, true);
    }

    /**
     * @param $var
     * @param null $message
     */
    public static function CleanHaltCL($var, $message = null): void
    {
        static::_DebugCL($var, $message, true, true, false);

    }

    /**
     * @param $var
     * @param null $message
     */
    public static function HaltCL($var, $message = null): void
    {
        static::_DebugCL($var, $message, true, true);
    }

    /**
     * @param $var
     * @param null $msg
     * @param bool $print
     * @param bool $exit
     * @param bool $backtrace
     */
    public static function _Debug($var, $msg = null, bool $print = false, bool $exit = false, bool $backtrace = true): void
    {
        global $Web;

        $finalMsg = '';
        if ($msg) {
            $finalMsg .= '<h3>' . $msg . '</h3>';
        }
        $finalMsg .= '<pre>';
        if (is_object($var)) {
            $finalMsg .= match (get_class($var)) {
                'Exception' => print_r($var->getMessage(), true),
                default => print_r($var, true),
            };
        } else {
            $finalMsg .= print_r($var, true);
        }
        $finalMsg .= "\r\n\r\n";
        if ($backtrace) {
            $finalMsg .= print_r($_SERVER, true);
            $finalMsg .= "\r\n\r\n";
            $finalMsg .= print_r($Web, true);
            $finalMsg .= "\r\n\r\n";
            $finalMsg .= print_r($_REQUEST, true);
            $finalMsg .= "\r\n\r\n";
            $finalMsg .= static::_debug_string_backtrace();
        }
        $finalMsg .= '</pre>' . PHP_EOL;

        if (defined('IS_PRODUCTION') && IS_PRODUCTION) {
            $uri = $_SERVER['REQUEST_URI'] ?? $_SERVER['SCRIPT_FILENAME'];
            if (defined('SMTP_DEBUG_EMAIL') && defined('SMTP_FROM_NAME')) {
                $t = Mailer::Queue(SMTP_DEBUG_EMAIL, SMTP_FROM_NAME, SMTP_FROM_NAME . ' HALT: ' . $uri, $finalMsg);
                try {
                    $t->Send();
                } catch (Exception $ex) {
                    echo $ex->getMessage();
                }
            }
            if (defined('SMTP_DEBUG_SMS')) {
                $t = Mailer::Queue(SMTP_DEBUG_SMS, SMTP_DEBUG_SMS, ' HALT: ' . $uri, 'There was a critical error.  Check email.');
                try {
                    $t->Send();
                } catch (Exception $ex) {
                    echo $ex->getMessage();
                }
            }
            if (defined('PRETTY_ERROR')) {
                exit(PRETTY_ERROR);
            }
            exit('An Error Occurred.  Please Try Again Later.');
        }
        if ($print !== false) {
            echo $finalMsg;
        }

        if ($exit) {
            exit(1);
        }
    }

    /**
     * @param      $var
     * @param null $msg
     * @param bool $print
     * @param bool $exit
     * @param bool $backtrace
     */
    private static function _DebugCL($var, $msg = null, bool $print = false, bool $exit = false, bool $backtrace = true): void
    {
        $res = "\n----\n";
        if ($msg) {
            echo $msg . PHP_EOL;
        }
        if (is_object($var) || is_array($var)) {
            $t = print_r($var, true);
        } else {
            $t = $var;
        }
        $res .= $t;
        $res .= "\n----\n";
        if ($backtrace)
            $res .= "\n----\n" . static::_debug_string_backtrace() . "\n----\n";

        if ($print) {
            echo $res;
        }

        if ($exit)
            exit(1);

        if (!$print) {
            trigger_error($res);
        }
    }

    /**
     * @return array|false|string|string[]|null
     */
    private static function _debug_string_backtrace(): array|bool|string|null
    {
        ob_start();
        debug_print_backtrace();
        $trace = ob_get_contents();
        ob_end_clean();

        // Remove first item from backtrace as it's this function which
        // is redundant.
        if (strlen($trace) < 1024 * 64) {
            $trace = preg_replace('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1);
        }


        return $trace;
    }

    /**
     * @param $errno
     * @return string
     */
    public static function _convert_error_no($errno): string
    {
        return match ($errno) {
            E_USER_ERROR => 'user error',
            E_USER_WARNING => 'user warning',
            E_USER_NOTICE => 'user notice',
            E_STRICT => 'strict',
            default => 'unknown',
        };
    }
}









