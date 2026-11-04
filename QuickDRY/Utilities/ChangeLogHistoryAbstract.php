<?php
declare(strict_types=1);


namespace QuickDRY\Utilities;


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