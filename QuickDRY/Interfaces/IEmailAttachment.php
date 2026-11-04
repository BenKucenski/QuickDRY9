<?php
declare(strict_types=1);

namespace QuickDRY\Interfaces;

/**
 *
 */
interface IEmailAttachment
{
    public ?string $FileName = null {
        get;
        set;
    }
    public ?string $FileLocation = null {
        get;
        set;
    }
}