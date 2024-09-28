<?php


namespace Bkucenski\Quickdry\Utilities;


/**
 *
 */
abstract class ChangeLogHistoryAbstract extends strongType
{
    public ?array $changes = null;

    public ?string $DB_HOST = null;
    public ?string $database = null;
    public ?string $table = null;
    public ?string $uuid = null;

}