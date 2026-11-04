<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

/**
 * Class SimpleExcel_Column
 */
class SimpleExcel_Column extends strongType
{
    public const int SIMPLE_EXCEL_PROPERTY_TYPE_CALCULATED = 0;
    public const int SIMPLE_EXCEL_PROPERTY_TYPE_AS_GIVEN = 1;
    public const int SIMPLE_EXCEL_PROPERTY_TYPE_DATE = 2;
    public const int SIMPLE_EXCEL_PROPERTY_TYPE_DATETIME = 3;
    public const int SIMPLE_EXCEL_PROPERTY_TYPE_CURRENCY = 4;
    public const int SIMPLE_EXCEL_PROPERTY_TYPE_HYPERLINK = 5;

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
        ?string $Header = null,
        ?string $Property = null,
        int     $PropertyType = self::SIMPLE_EXCEL_PROPERTY_TYPE_CALCULATED
    )
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

    /**
     * @param string $className
     * @param string $propertyName
     * @return int|null
     */
    public static function getPropertyType(
        string $className,
        string $propertyName
    ): ?int {

        $type_name = null;

        try {
            $reflection = new ReflectionProperty($className, $propertyName);
            $type = $reflection->getType();

            if ($type instanceof ReflectionNamedType) {
                $type_name =  $type->getName();
            } elseif ($type instanceof ReflectionUnionType) {
                $type_name = implode('|', array_map(fn($t) => $t->getName(), $type->getTypes()));
            }
        } catch (ReflectionException $e) {
            Exception($e->getMessage());
        }

        switch($type_name) {
            case 'string':
            case 'array':
                return self::SIMPLE_EXCEL_PROPERTY_TYPE_AS_GIVEN;

            case 'DateTime':
                return self::SIMPLE_EXCEL_PROPERTY_TYPE_DATE;

            case 'int':
            case 'float':
            case 'bool':
                return self::SIMPLE_EXCEL_PROPERTY_TYPE_CALCULATED;

            default:
                Exception($type_name);
        }
    }
}