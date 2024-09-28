<?php

/**
 * @param ...$args
 */
function Debug(...$args): void
{
    $code = time() . '.' . rand(0, 1000000);
    if(!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs');
    }

    $file = __DIR__ . '/logs/' . $code . '.txt';
    file_put_contents($file, json_encode(['data' => $args, 'backtrace' => debug_backtrace()], JSON_PRETTY_PRINT));

    if(!defined('CONST_OUTPUT_ERRORS') || !CONST_OUTPUT_ERRORS) {
        exit('<p>An Error Occurred: ' . $code . '</p>');
    }

    if(defined('HALT_ON_DEBUG') && HALT_ON_DEBUG) {
        dd(['data' => $args, 'backtrace' => debug_backtrace()]);
    }
}