<?php
declare(strict_types=1);

namespace QuickDRY\Interfaces;

/**
 *
 */
interface IAPIUser
{
    public ?string $client_id = null {
        get;
        set;
    }
    public ?string $email_address = null {
        get;
        set;
    }

    /**
     * @param string $client_id
     * @return self
     */
    public static function GetForClientId(string $client_id): self;

    /**
     * @param string $client_secret
     * @return bool
     */
    public function validate(string $client_secret): bool;
}