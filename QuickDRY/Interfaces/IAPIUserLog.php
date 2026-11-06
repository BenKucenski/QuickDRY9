<?php
declare(strict_types=1);

namespace QuickDRY\Interfaces;

/**
 *
 */
interface IAPIUserLog
{
    public ?string $client_id = null {
        get;
        set;
    }
    public ?string $created_at = null {
        get;
        set;
    }
    public ?string $remote_addr = null {
        get;
        set;
    }
    public ?int $is_success = null {
        get;
        set;
    }

    public string|null $host {
        get;
        set;
    }


    /**
     * @return void
     */
    public function Save(): void;
}