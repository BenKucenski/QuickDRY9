<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

/**
 * Class LogFile
 *
 * Writes to DATA_FOLDER/logs/<date>/. DATA_FOLDER must already exist (see
 * DataFolder); a missing or unwritable one throws instead of writing
 * somewhere else.
 */
class LogFile
{
    private static array $StartTime;

    public function __construct()
    {
        DataFolder::Folder('logs');
    }

    /**
     * @param $filename
     * @param $message
     * @param bool $write_to_file
     */
    public function Insert($filename, $message, bool $write_to_file = true): void
    {
        if (is_object($message)) {
            if (method_exists($message, 'GetMessage')) {
                $message = $message->GetMessage();
            }
        }
        if (!isset(self::$StartTime[GUID])) {
            self::$StartTime[GUID] = time();
        }

        $msg = [];
        $msg [] = GUID;
        $msg [] = sprintf('%08.2f', (time() - self::$StartTime[GUID]) / 60);
        $msg [] = Dates::Timestamp();
        $msg [] = getcwd() . '/' . $filename;
        $msg [] = Network::Interfaces();
        $msg [] = is_array($message) || is_object($message) ? json_encode($message) : $message;
        $msg = implode("\t", $msg);

        $date = Dates::Datestamp();

        if ($write_to_file) {
            $f = preg_replace('/[^a-z0-9]/si', '_', $filename) . '.' . Dates::Datestamp();
            $log_path = DataFolder::File('logs/' . $date . '/' . $f . '.log');

            file_put_contents($log_path, $msg . PHP_EOL, FILE_APPEND);
        }

        if (SHOW_ERRORS || SHOW_NOTICES) {
            if(!($_SERVER['HTTP_HOST'] ?? null)) {
                echo $msg . PHP_EOL;
            }
        }
    }
}