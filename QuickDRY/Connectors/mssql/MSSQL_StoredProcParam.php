<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mssql;

use QuickDRY\Utilities\strongType;

/**
 *
 */
class MSSQL_StoredProcParam extends strongType
{
    public ?string $StoredProc = null;
    public ?string $Parameter_name = null;
    public ?string $Type = null;
    public ?int $Length = null;
    public ?int $Prec = null;
    public ?int $Scale = null;
    public ?int $Param_order = null;
    public ?string $Collation = null;
}