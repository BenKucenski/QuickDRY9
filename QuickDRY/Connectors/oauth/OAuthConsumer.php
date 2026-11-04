<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\oauth;

use QuickDRY\Utilities\strongType;

/**
 *
 */
class OAuthConsumer extends strongType
{
    public ?string $key;
    public ?string $secret;
    public ?string $callback_url;

    /**
     * @param string $key
     * @param string $secret
     * @param string|NULL $callback_url
     */
    public function __construct(
        string  $key,
        string  $secret,
        ?string $callback_url = NULL)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->callback_url = $callback_url;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "OAuthConsumer[key=$this->key,secret=$this->secret]";
    }
}