<?php
declare(strict_types=1);

namespace QuickDRY\Connectors;

use QuickDRY\Utilities\strongType;

/**
 * Class CurlHeader
 */
class CurlHeader extends strongType
{
    public ?string $CacheControl = null;
    public ?string $Pragma = null;
    public ?string $ContentType = null;
    public ?string $Expires = null;
    public ?string $Server = null;
    public ?string $AccessControlAllowOrigin = null;
    public ?string $AccessControlAllowCredentials = null;
    public ?string $AccessControlAllowMethods = null;
    public ?string $AccessControlAllowHeaders = null;
    public ?string $Date = null;
    public ?string $ContentLength = null;
    public ?string $Location = null;

    /**
     * CurlHeader constructor.
     * @param null $row
     */
    public function __construct($row = null)
    {
        parent::__construct();
        foreach ($row as $k => $v) {
            if (str_starts_with($k, 'X-')) {
                continue;
            }
            switch ($k) {
                case 'Content-Length':
                    $this->ContentLength = $v;
                    break;
                case 'Date':
                    $this->Date = $v;
                    break;
                case 'Access-Control-Allow-Origin':
                    $this->AccessControlAllowOrigin = $v;
                    break;
                case 'Access-Control-Allow-Credentials':
                    $this->AccessControlAllowCredentials = $v;
                    break;
                case 'Access-Control-Allow-Methods':
                    $this->AccessControlAllowMethods = $v;
                    break;
                case 'Access-Control-Allow-Headers':
                    $this->AccessControlAllowHeaders = $v;
                    break;
                case 'Server':
                    $this->Server = $v;
                    break;
                case 'Expires':
                    $this->Expires = $v;
                    break;
                case 'Content-Type':
                    $this->ContentType = $v;
                    break;
                case 'Pragma':
                    $this->Pragma = $v;
                    break;
                case 'Cache-Control':
                    $this->CacheControl = $v;
                    break;
                case 'Location':
                    $this->Location = $v;
                    break;
                default:
            }
        }
    }
}