<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\oauth;


/**
 *
 */
class OAuthDataStore
{
    /**
     * @param $consumer_key
     * @return OAuthConsumer|null
     */
    public function lookup_consumer($consumer_key): ?OAuthConsumer
    {
        // implement me
        return null;
    }

    /**
     * @param $consumer
     * @param $token_type
     * @param $token
     * @return OAuthToken|null
     */
    public function lookup_token($consumer, $token_type, $token): ?OAuthToken
    {
        // implement me
        return null;
    }

    /**
     * @param $consumer
     * @param $token
     * @param $nonce
     * @param $timestamp
     * @return null
     */
    public function lookup_nonce($consumer, $token, $nonce, $timestamp): null
    {
        // implement me
        return null;
    }

    /**
     * @param $consumer
     * @param callable|null $callback
     * @return OAuthToken|null
     */
    public function new_request_token($consumer, ?callable $callback = null): ?OAuthToken
    {
        // return a new token attached to this consumer
        return null;
    }

    /**
     * @param OAuthToken $token
     * @param OAuthConsumer $consumer
     * @param string|null $verifier
     * @return OAuthToken|null
     */
    public function new_access_token(OAuthToken $token, OAuthConsumer $consumer, ?string $verifier = null): ?OAuthToken
    {
        // return a new access token attached to this consumer
        // for the user associated with this token if the request token
        // is authorized
        // should also invalidate the request token
        return null;
    }

}