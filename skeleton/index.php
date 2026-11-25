<?php
declare(strict_types=1);

use QuickDRY\Utilities\Dates;
use QuickDRY\Utilities\Log;
use QuickDRY\Utilities\Metrics;
use QuickDRY\Web\Web;

$__page_start_time = microtime(true);

/**
 * @return void
 */
function shutdown(): void
{
    global $__page_start_time;
    $page_view = new stdClass();
    $page_view->host = $_SERVER['HTTP_HOST'];
    $page_view->path = $_SERVER['REQUEST_URI'];
    $page_view->remote_addr = $_SERVER['REMOTE_ADDR'];
    $page_view->created_at = Dates::Timestamp();
    $page_view->user = $_SESSION['user'] ?? '';
    $page_view->time_to_render = microtime(true) - $__page_start_time;
    $page_view->metrics = Metrics::ToString();
    Log::Insert($page_view); // or store it in a database
}

register_shutdown_function('shutdown');

require_once 'modules.php';

// Session cookie: not readable by JavaScript, not sent on cross-site POSTs, HTTPS-only over HTTPS
// (X-Forwarded-Proto covers a TLS-terminating proxy or tunnel in front of the site).
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_name(SESSION_NAME);
session_start();

global $web;

$web = new Web();

$web->Init(
    '/home',
    '/home',
    __DIR__
);

// Require the CSRF token on everything that can change data. Pages need Csrf::MetaTag() in <head>
// and /QuickDRY/js/QuickDRY.Csrf.js after jQuery (see README).
// $web->RequireCSRFToken = true;

$web->Exec();
