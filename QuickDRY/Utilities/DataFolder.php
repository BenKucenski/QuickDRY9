<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

use RuntimeException;

/**
 * The one place QuickDRY works out where it may write files (logs, SQL
 * logs, and anything an app stores through it).
 *
 * DATA_FOLDER must be defined by the app as an ABSOLUTE path to a folder
 * that already exists and is writable. Nothing here falls back to the
 * current folder or the web root, and DATA_FOLDER itself is never
 * created: if it's missing, that's a configuration error and it's
 * reported, not papered over. Only sub-folders inside it are created.
 */
class DataFolder
{
    /**
     * The validated DATA_FOLDER, without a trailing slash.
     *
     * @throws RuntimeException when DATA_FOLDER is missing or unusable
     */
    public static function Root(): string
    {
        if (!defined('DATA_FOLDER')) {
            throw new RuntimeException('DATA_FOLDER is not defined. Define it in your configuration as the absolute path of a writable folder outside the web root.');
        }

        $root = trim((string)DATA_FOLDER);
        if ($root === '') {
            throw new RuntimeException('DATA_FOLDER is empty. Set it to the absolute path of a writable folder outside the web root.');
        }

        if (!self::IsAbsolute($root)) {
            throw new RuntimeException('DATA_FOLDER must be an absolute path, not "' . $root . '" (a relative path depends on the current folder).');
        }

        $root = rtrim($root, '/\\');
        if ($root === '') {
            $root = '/';
        }

        if (!is_dir($root)) {
            throw new RuntimeException('DATA_FOLDER "' . $root . '" does not exist. Create it (outside the web root) and make it writable by the web server.');
        }

        if (!is_writable($root)) {
            throw new RuntimeException('DATA_FOLDER "' . $root . '" is not writable by the web server.');
        }

        return $root;
    }

    /**
     * Absolute path of a folder inside DATA_FOLDER, creating it (and any
     * folders between) if needed.
     *
     * @param string $relative e.g. "logs" or "logs/2026-11-11"
     * @throws RuntimeException
     */
    public static function Folder(string $relative): string
    {
        $relative = trim(str_replace('\\', '/', $relative), '/');
        if ($relative === '' || preg_match('#(^|/)\.\.(/|$)#', $relative)) {
            throw new RuntimeException('Invalid DATA_FOLDER sub-folder "' . $relative . '".');
        }

        $path = self::Root() . '/' . $relative;
        // @ because the failure is reported below, with PHP's reason
        if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {
            throw new RuntimeException('Could not create "' . $path . '": ' . (error_get_last()['message'] ?? 'unknown error'));
        }

        return $path;
    }

    /**
     * Absolute path of a file inside DATA_FOLDER; its folder is created
     * if needed.
     *
     * @param string $relative e.g. "logs/123.txt"
     * @throws RuntimeException
     */
    public static function File(string $relative): string
    {
        $relative = trim(str_replace('\\', '/', $relative), '/');
        $dir = dirname($relative);
        $folder = $dir === '.' ? self::Root() : self::Folder($dir);
        $name = basename($relative);
        if ($name === '' || $name === '.' || $name === '..') {
            throw new RuntimeException('Invalid DATA_FOLDER file "' . $relative . '".');
        }
        return $folder . '/' . $name;
    }

    private static function IsAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\\\')                 // \\server\share
            || (bool)preg_match('#^[A-Za-z]:[\\\\/]#', $path); // C:\ or C:/
    }
}
