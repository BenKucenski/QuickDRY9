<?php

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

session_name(SESSION_NAME);
session_start();

global $web;

$web = new Web();

$web->Init(
    '/home',
    '/home',
    __DIR__
);

$web->Exec();
