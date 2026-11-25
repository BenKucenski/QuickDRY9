<?php
declare(strict_types=1);

namespace QuickDRY\Web;

use QuickDRY\Utilities\HTTP;

/**
 * Cross-site request forgery protection.
 *
 * Every session gets a random token. A page puts it in its <head> with MetaTag();
 * /QuickDRY/js/QuickDRY.Csrf.js then sends it with every same-origin jQuery request and fetch()
 * (X-CSRF-Token header) and adds it to every POST form (csrf_token field).
 *
 * Turn it on with `$web->RequireCSRFToken = true;` in index.php, or call Check() yourself.
 * Requests that can change data need the token: any HTTP method other than GET/HEAD, and any
 * request whose verb (see Web::ResolveVerb) isn't a read-only one.
 */
class Csrf
{
    public const string FIELD = 'csrf_token';
    public const string HEADER = 'HTTP_X_CSRF_TOKEN';

    /**
     * This session's token (created on first use). Needs an active session.
     */
    public static function Token(): string
    {
        if (empty($_SESSION[self::FIELD]) || !is_string($_SESSION[self::FIELD])) {
            $_SESSION[self::FIELD] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::FIELD];
    }

    /**
     * <meta name="csrf-token" content="..."> for the page's <head>.
     */
    public static function MetaTag(): string
    {
        return '<meta name="csrf-token" content="' . htmlspecialchars(self::Token(), ENT_QUOTES) . '">';
    }

    /**
     * <input type="hidden" name="csrf_token" value="..."> for forms built without the JS helper.
     */
    public static function HiddenInput(): string
    {
        return '<input type="hidden" name="' . self::FIELD . '" value="' . htmlspecialchars(self::Token(), ENT_QUOTES) . '">';
    }

    /**
     * @param string|null $verb the resolved verb (Web::$Verb); defaults to the HTTP method
     */
    public static function IsStateChanging(?string $verb = null): bool
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $verb = strtoupper($verb ?? $method);
        return !in_array($method, ['GET', 'HEAD'], true) || !in_array($verb, Web::READ_VERBS, true);
    }

    /**
     * Whether the request carries this session's token.
     */
    public static function Valid(): bool
    {
        $sent = $_SERVER[self::HEADER] ?? ($_POST[self::FIELD] ?? null);
        $expected = $_SESSION[self::FIELD] ?? null;
        return is_string($sent) && is_string($expected) && $expected !== '' && hash_equals($expected, $sent);
    }

    /**
     * Stop a state-changing request that doesn't carry the token: JSON 403 for AJAX/JSON
     * requests, otherwise back to the page with an error.
     *
     * @param string|null $verb
     */
    public static function Check(?string $verb = null): void
    {
        if (!self::IsStateChanging($verb) || self::Valid()) {
            return;
        }

        $message = 'Your session expired. Reload the page and try again.';
        $wants_json = isset($_SERVER[self::HEADER])
            || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
            || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/json/');

        if ($wants_json) {
            HTTP::ExitJSON(['error' => $message], 403);
        }
        HTTP::RedirectError($message, strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/');
    }
}
