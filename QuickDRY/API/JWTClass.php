<?php
declare(strict_types=1);

namespace QuickDRY\API;

use QuickDRY\Utilities\strongType;

/**
 *
 */
class JWTClass extends strongType
{
    public ?int $iat = null; // issue timestamp
    public ?string $iss = null; // host
    public ?int $nbf = null; //
    public ?int $exp = null; //
    public ?array $data = null; //
}