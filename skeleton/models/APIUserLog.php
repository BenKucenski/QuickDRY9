<?php
declare(strict_types=1);

namespace models;

use QuickDRY\Interfaces\IAPIUserLog;

/**
 *
 */
class APIUserLog implements IAPIUserLog
{

    public null|string $client_id {
        get {
            return $this->client_id;
        }
        set {
            $this->client_id = $value;
        }
    }
    public null|string $created_at {
        get {
            return $this->created_at;
        }
        set {
            $this->created_at = $value;
        }
    }
    public null|string $remote_addr {
        get {
            return $this->remote_addr;
        }
        set {
            $this->remote_addr = $value;
        }
    }
    public int|null $is_success {
        get {
            return $this->is_success;
        }
        set {
            $this->is_success = $value;
        }
    }

    public string|null $host {
        get {
            return $this->host;
        }
        set {
            $this->host = $value;
        }
    }

    /**
     * @return void
     */
    public function Save(): void
    {
        // TODO: Implement Save() method.
    }
}