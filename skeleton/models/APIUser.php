<?php
declare(strict_types=1);

namespace models;

use QuickDRY\Interfaces\IAPIUser;

/**
 *
 */
class APIUser implements IAPIUser
{

    public null|string $client_id {
        get {
            return $this->client_id;
        }
        set {
            $this->client_id = $value;
        }
    }
    public null|string $email_address {
        get {
            return $this->email_address;
        }
        set {
            $this->email_address = $value;
        }
    }

    /**
     * @param string $client_id
     * @return ?self
     */
    public static function GetForClientId(string $client_id): ?IAPIUser
    {
        // TODO: Implement GetForClientId() method.
        return new APIUser();
    }

    /**
     * @param string $client_secret
     * @return bool
     */
    public function validate(string $client_secret): bool
    {
        // TODO: Implement validate() method.
        return true;
    }
}