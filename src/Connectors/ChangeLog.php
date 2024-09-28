<?php

namespace Bkucenski\Quickdry\Connectors;

use Bkucenski\Quickdry\Utilities\strongType;

/**
 * Class ChangeLog
 */
class ChangeLog extends strongType
{
    public string $host;
    public ?string $database;
    public ?string $table;
    public string $uuid;
    public string $changes;
    public ?int $user_id;
    public ?string $created_at;
    public string $object_type;
    public bool $is_deleted;

    /**
     * @return void
     */
    public function Save(): void
    {

    }
}