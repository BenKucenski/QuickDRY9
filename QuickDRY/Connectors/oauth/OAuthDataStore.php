<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\oauth;


/**
 *
 */
interface OAuthDataStore
{
    /**
     * @param $consumer_key
     * @return OAuthConsumer|null
     */
    public function lookup_consumer($consumer_key): ?OAuthConsumer;

    /**
     * @param $consumer
     * @param $token_type
     * @param $token
     * @return OAuthToken|null
     */
    public function lookup_token($consumer, $token_type, $token): ?OAuthToken;

    /**
     * @param $consumer
     * @param $token
     * @param $nonce
     * @param $timestamp
     * @return null
     */
    public function lookup_nonce($consumer, $token, $nonce, $timestamp): null;

    /**
     * @param $consumer
     * @param callable|null $callback
     * @return OAuthToken|null
     */
    public function new_request_token($consumer, ?callable $callback = null): ?OAuthToken;

    /**
     * @param OAuthToken $token
     * @param OAuthConsumer $consumer
     * @param string|null $verifier
     * @return OAuthToken|null
     */
    public function new_access_token(OAuthToken $token, OAuthConsumer $consumer, ?string $verifier = null): ?OAuthToken;
}