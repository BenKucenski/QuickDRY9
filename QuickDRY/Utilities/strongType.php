<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;


use DateTime;
use Exception;
use JsonSerializable;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use ReflectionProperty;
use SplObjectStorage;
use Throwable;

/**
 *
 */
class strongType implements JsonSerializable
{
    private array $_missing_properties = [];
    protected static ?array $_alias = null;

    /**
     * @return array
     */
    public static function dumpStatic(): array
    {
        // Use Reflection to access all static properties, including protected/private ones
        $reflection = new ReflectionClass(static::class);
        $staticProperties = $reflection->getProperties(ReflectionProperty::IS_STATIC);

        $data = [];

        foreach ($staticProperties as $property) {
            $data[$property->getName()] = $property->getValue();
        }

        // Dump all static properties and their values
        return $data;
    }

    /**
     * @return void
     */
    public function isMissingProperties(): void
    {
        self::checkMissingProperties($this->_missing_properties, static::class);
    }


    /**
     * @param $value
     * @return string
     */
    private static function inferType($value): string
    {
        return match (gettype($value)) {
            'integer' => 'int',
            'double' => 'float',
            'boolean' => 'bool',
            'string' => 'string',
            'array' => 'array',
            'object' => '\\' . get_class($value),
            default => 'mixed',
        };
    }


    /**
     * @param array $missing_properties
     * @param string $class
     * @return void
     */
    public static function checkMissingProperties(array $missing_properties, string $class): void
    {
        if (!CONST_HALT_ON_MISSING_PARAMS) {
            return;
        }
        if (!sizeof($missing_properties)) {
            return;
        }
        $code = [];
        foreach ($missing_properties as $key => $val) {
            if (is_array($val)) {
                $code[] = 'public ?array $' . $key . ' = null; // ' . json_encode($val);
            } elseif (is_object($val) && get_class($val) === DateTime::class) {
                $code[] = 'public ?DateTime $' . $key . ' = null; // ' . Dates::Timestamp($val);
            } else {
                $code[] = 'public ?' . self::inferType($val) . ' $' . $key . ' = null; // ' . json_encode($val);
            }
        }
        Exception([
            implode("\r\n", $code),
            $class . ' missing properties' => $missing_properties,
            'backtrace'                    => debug_backtrace()
        ]);
    }

    /**
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        $this->_missing_properties[$name] = null;
        self::checkMissingProperties($this->_missing_properties, static::class);
        return null;
    }

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        if (isset(static::$_alias[$name])) {
            $name = static::$_alias[$name];
        }

        if (!property_exists(static::class, $name)) {
            $this->_missing_properties[$name] = $value;
        } else {
            $this->$name = $value;
        }

        return $value;
    }

    /**
     * @param bool $exclude_empty
     * @return array
     */
    public function toArray(bool $exclude_empty = false): array
    {
        $values = [];
        $ref = new ReflectionObject($this);

        // Only grab public properties; change to getProperties() with flags if you want protected too
        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            // Skip static properties
            if ($prop->isStatic()) {
                continue;
            }

            $name = $prop->getName();
            $val  = $this->$name;

            if ($exclude_empty && $val === null) {
                continue;
            }

            // Skip internal/hidden keys like "_foo"
            if ($name[0] === '_') {
                continue;
            }

            // Handle special types
            if ($val instanceof DateTime) {
                $val = Dates::Timestamp($val);
            }

            $values[$name] = $val;
        }

        return $values;
    }


    /**
     * @param array $data
     * @return void
     */
    public function fromRequest(array $data): void
    {
        $this->fromData($data, false);
    }

    /**
     * @param array $data
     * @param bool $strict
     * @return $this
     */
    public function fromData(array $data, bool $strict = true): strongType
    {
        foreach ($data as $k => $v) {
            if (is_numeric($k[0])) {
                $k = '_' . $k;
            }

            if($v === 'null') {
                $v = null;
            }

            try {
                $rp = new ReflectionProperty(static::class, $k);
            } catch (ReflectionException $e) {
                $this->$k = $v;
                continue;
            }

            if (!$rp || !$rp->getType()) {
                $this->$k = $v;
                continue;
            }

            switch ($rp->getType()->getName()) {
                case 'DateTime':
                    try {
                        $this->$k = ($v && is_string($v)) ? new DateTime($v) : $v;
                    } catch (Exception $e) {
                        Exception($v, $e->getMessage());
                    }
                    break;

                case 'array':
                case 'enum':
                case 'string':
                    $this->$k = $v;
                    break;

                case 'float':
                    $this->$k = floatval($v);
                    break;

                case 'int':
                    $this->$k = intval($v);
                    break;

                case 'bool':
                    $this->$k = boolval($v);
                    break;

                default:
                    if (!$v) {
                        $this->$k = $v;
                        break;
                    }
                    if (is_object($v)) {
                        $this->$k = $v;
                        break;
                    }
                    Exception($rp->getType()->getName() . ' unknown type', debug_backtrace());
            }
        }

        if ($strict) {
            self::checkMissingProperties($this->_missing_properties, static::class);
        }

        return $this;
    }

    /**
     * @param array|null $data
     * @param object|null $item
     */
    public function __construct(?array $data = null, ?object $item = null)
    {
        if ($data) {
            $this->fromData($data);
        }

        if ($item) {
            $data = json_decode(json_encode($item), true);
            $this->fromData($data);
        }
    }

    /**
     * @param strongType $item
     * @return array
     */
    public static function getHeaders(strongType $item): array
    {
        $class = get_called_class();
        $cols = array_keys($item->toArray());
        foreach ($cols as $i => $col) {
            if (isset($class::$_alias)) {
                if (array_key_exists($col, static::$_alias) && is_null(static::$_alias[$col])) {
                    unset($cols[$i]);
                }
            }
        }
        return $cols;
    }

    /**
     * @param strongType[] $items
     * @param string $filename
     *
     * pass in an array of SafeClass objects and the file name
     */
    public static function toCSV(
        array  $items,
        string $filename
    ): void
    {
        if (!sizeof($items)) {
            Exception('QuickDRY Error: Not an array or empty');
        }

        $cols = self::getHeaders($items[0]);

        if (isset($_SERVER['HTTP_HOST'])) {
            $output = fopen('php://output', 'w') or die("Can't open php://output");
            header('Content-Type:application/csv');
            header("Content-Disposition:attachment;filename=\"" . $filename . "\"");
        } else {
            $output = fopen($filename, 'w');
        }
        if (!$output) {
            Exception('could not open ' . $filename);
        }

        fputcsv($output, $cols);
        foreach ($items as $item) {
            $row = [];
            foreach ($cols as $col) {
                if (property_exists($item, $col)) {
                    $row[] = $item->$col;
                } elseif ($key = array_search($col, self::$_alias)) {
                    $row[] = $item->$key;
                }
            }
            fputcsv($output, $row);
        }
        fclose($output) or die("Can't close php://output");
    }

    /**
     * @param strongType[] $items
     * @return SimpleExcel|null
     */
    public static function toExcel(?array $items): ?SimpleExcel
    {
        if (!$items || !sizeof($items)) {
            return null;
        }
        $cols = static::getHeaders($items[0]);

        $se = new SimpleExcel();
        $se->Report = $items;
        $se->Title = static::class;
        $se->Columns = [];
        foreach ($cols as $col) {
            $se->Columns[$col] = new SimpleExcel_Column(
                static::$_alias[$col] ?? null,
                $col,
                SimpleExcel_Column::getPropertyType(static::class, $col)
            );
        }
        return $se;
    }

    /**
     * Render a single object as a vertical (key/value) HTML table (recursive).
     *
     * @param object|null $item
     * @param string|null $class
     * @param string|null $style
     * @param bool $exclude_empty
     * @param int $maxDepth
     * @return string
     */
    public static function ToHTML_VerticalRecursive(
        ?object $item,
        ?string $class = 'table table-sm mb-0',
        ?string $style = '',
        bool $exclude_empty = false,
        int $maxDepth = 6
    ): string
    {
        if (!$item) {
            return '';
        }

        $seen = new SplObjectStorage();

        return self::renderVerticalTable(
            $item,
            (string)$class,
            (string)$style,
            $exclude_empty,
            0,
            $maxDepth,
            $seen,
            'root'
        );
    }

    /**
     * @param mixed $value
     * @param string $class
     * @param string $style
     * @param bool $exclude_empty
     * @param int $depth
     * @param int $maxDepth
     * @param SplObjectStorage $seen
     * @param string $path
     * @return string
     */
    private static function renderVerticalTable(
        mixed $value,
        string $class,
        string $style,
        bool $exclude_empty,
        int $depth,
        int $maxDepth,
        SplObjectStorage $seen,
        string $path
    ): string
    {
        // Depth guard
        if ($depth > $maxDepth) {
            return '<div class="text-muted small">Max depth reached</div>';
        }

        // Normalize object -> array of properties
        if (is_object($value)) {
            if ($seen->contains($value)) {
                return '<div class="text-muted small">↩︎ Circular reference</div>';
            }
            $seen->attach($value);

            if ($value instanceof self) {
                $data = $value->toArray($exclude_empty);
            } else {
                $data = get_object_vars($value);
                if ($exclude_empty) {
                    $data = array_filter($data, static fn($v) => $v !== null);
                }
            }
        } elseif (is_array($value)) {
            $data = $value;
            if ($exclude_empty) {
                $data = array_filter($data, static fn($v) => $v !== null);
            }
        } else {
            // Scalar fallback
            return '<span>' . htmlspecialchars(self::formatScalar($value)) . '</span>';
        }

        $html  = '<table class="' . htmlspecialchars($class) . '" style="' . htmlspecialchars($style) . '">';
        $html .= '<tbody>';

        foreach ($data as $key => $val) {
            $keyStr = (string)$key;
            if ($keyStr === '') {
                continue;
            }
            if ($keyStr[0] === '_') {
                continue;
            }

            $rowKey = htmlspecialchars($keyStr);

            // Scalar / DateTime
            if (!is_array($val) && !is_object($val)) {
                $display = htmlspecialchars(self::formatScalar($val));
                $html .= '<tr>';
                $html .= '<th scope="row" style="white-space:nowrap;vertical-align:top;">' . $rowKey . '</th>';
                $html .= '<td style="white-space:pre-wrap;">' . $display . '</td>';
                $html .= '</tr>';
                continue;
            }

            // Nested array/object -> collapsible sub-table
            $collapseId = 'st_' . substr(sha1($path . '.' . $keyStr . '.' . $depth), 0, 12);

            $summary = is_object($val)
                ? ('Object: ' . get_class($val))
                : ('Array(' . count($val) . ')');

            $html .= '<tr>';
            $html .= '<th scope="row" style="white-space:nowrap;vertical-align:top;">' . $rowKey . '</th>';
            $html .= '<td>';

            $html .= '<div class="d-flex align-items-center gap-2">';
            $html .= '<button class="btn btn-sm btn-outline-secondary" type="button" '
                . 'data-bs-toggle="collapse" data-bs-target="#' . $collapseId . '" '
                . 'aria-expanded="false" aria-controls="' . $collapseId . '">'
                . 'View'
                . '</button>';
            $html .= '<span class="text-muted small">' . htmlspecialchars($summary) . '</span>';
            $html .= '</div>';

            $html .= '<div id="' . $collapseId . '" class="collapse mt-2">';
            $html .= '<div class="border rounded p-2 bg-light">';
            $html .= self::renderVerticalTable(
                $val,
                $class,
                '', // style for nested
                $exclude_empty,
                $depth + 1,
                $maxDepth,
                $seen,
                $path . '.' . $keyStr
            );
            $html .= '</div></div>';

            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * @param mixed $v
     * @return string
     */
    private static function formatScalar(mixed $v): string
    {
        if ($v instanceof DateTime) {
            return Dates::Timestamp($v);
        }
        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }
        if ($v === null) {
            return 'null';
        }
        if (is_float($v) && (is_nan($v) || is_infinite($v))) {
            return (string)$v;
        }
        return (string)$v;
    }

    /**
     * Render a single object as a vertical (key/value) HTML table.
     *
     * Left column: property name
     * Right column: value
     *
     * @param object|null $item
     * @param string|null $class  CSS class for <table>
     * @param string|null $style  inline style for <table>
     * @param bool $exclude_empty skip null values
     * @return string
     */
    public static function ToHTML_Vertical(
        ?object $item,
        ?string $class = '',
        ?string $style = '',
        bool $exclude_empty = false
    ): string
    {
        if (!$item) {
            return '';
        }

        // If it's a strongType instance, use toArray() so DateTime conversion & alias rules can apply there if you want.
        // Otherwise, fall back to public props.
        if ($item instanceof self) {
            $data = $item->toArray($exclude_empty);
        } else {
            $data = get_object_vars($item);
            if ($exclude_empty) {
                $data = array_filter($data, static fn($v) => $v !== null);
            }
        }

        $html = '<table class="' . htmlspecialchars((string)$class) . '" style="' . htmlspecialchars((string)$style) . '">';
        $html .= '<tbody>';

        foreach ($data as $key => $val) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            // Skip internal/hidden keys like "_foo"
            if ($key[0] === '_') {
                continue;
            }

            // Value formatting
            if ($val instanceof DateTime) {
                $display = Dates::Timestamp($val);
            } elseif (is_object($val)) {
                // Try Dates::Datestamp() like your ToHTML() does; if it fails, fall back safely.
                try {
                    $display = Dates::Datestamp($val);
                } catch (Throwable $e) {
                    $display = get_class($val);
                }
            } elseif (is_array($val)) {
                // Keep consistent with your ToHTML() which "continue;" on arrays:
                continue;

                // Alternative if you'd rather show arrays:
                // $display = '<pre class="mb-0 small">' . htmlspecialchars(json_encode($val, JSON_PRETTY_PRINT)) . '</pre>';
            } else {
                $display = (string)$val;
            }

            $html .= '<tr>';
            $html .= '<th scope="row" style="white-space:nowrap;vertical-align:top;">' . htmlspecialchars($key) . '</th>';
            $html .= '<td>' . htmlspecialchars($display) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * @param self[] $items
     * @param string|null $class
     * @param string|null $style
     * @param bool $numbered
     * @param int|null $limit
     * @return string
     */
    public static function ToHTML(
        array $items,
        ?string $class = '',
        ?string $style = '',
        ?bool $numbered = false,
        ?int $limit = 0
    ): string
    {
        if (!sizeof($items)) {
            return '';
        }

        $obj_class = get_called_class();
        $cols = array_keys(get_object_vars($items[0]));

        $se = new SimpleExcel();
        $se->Report = $items;
        $se->Title = $obj_class;
        $se->Columns = [];
        foreach ($cols as $col) {
            if($col[0] == '_') {
                continue;
            }
            $se->Columns[$col] = new SimpleExcel_Column(null, $col);
        }

        $html = '<table class="' . $class . '" style="' . $style . '"><thead><tr>';
        if ($numbered) {
            $html .= '<th></th>';
        }
        foreach ($se->Columns as $col => $settings) {
            if($col[0] == '_') {
                continue;
            }
            $html .= '<th>' . $col . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($se->Report as $i => $item) {
            if ($limit && $i >= $limit) {
                break;
            }
            $html .= '<tr>';
            if ($numbered) {
                $html .= '<td>' . ($i + 1) . '</td>';
            }
            foreach ($se->Columns as $col => $settings) {
                if($col[0] == '_') {
                    continue;
                }
                if (is_array($item->$col)) {
                    continue;
                }
                if (is_object($item->$col)) {
                    $html .= '<td>' . Dates::Datestamp($item->$col) . '</td>';
                } else {
                    $html .= '<td>' . ($item->$col) . '</td>';
                }
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table>';
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}