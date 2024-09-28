<?php

namespace Bkucenski\Quickdry\Utilities;

/**
 * Class SimpleExcel_Column
 */
class SimpleExcel_Column extends strongType
{
    public const SIMPLE_EXCEL_PROPERTY_TYPE_CALCULATED = 0;
    public const SIMPLE_EXCEL_PROPERTY_TYPE_AS_GIVEN = 1;
    public const SIMPLE_EXCEL_PROPERTY_TYPE_DATE = 2;
    public const SIMPLE_EXCEL_PROPERTY_TYPE_DATETIME = 3;
    public const SIMPLE_EXCEL_PROPERTY_TYPE_CURRENCY = 4;
    public const SIMPLE_EXCEL_PROPERTY_TYPE_HYPERLINK = 5;

    public ?string $Header;
    public ?string $Property;
    public int $PropertyType;

    /**
     * SimpleExcel_Column constructor.
     * @param string|null $Header
     * @param string|null $Property
     * @param int $PropertyType
     */
    public function __construct(
        string $Header = null,
        string $Property = null,
        int    $PropertyType = self::SIMPLE_EXCEL_PROPERTY_TYPE_CALCULATED)
    {
        $this->Header = is_null($Header) ? $Property : $Header;
        $this->Property = is_null($Property) ? $Header : $Property;
        $this->PropertyType = $PropertyType;
    }

    /**
     * @param $PropertyType
     */
    public function SetPropertyType($PropertyType): void
    {
        $this->PropertyType = $PropertyType;
    }
}