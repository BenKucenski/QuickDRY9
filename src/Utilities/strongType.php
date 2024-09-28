<?php

namespace Bkucenski\Quickdry\Utilities;


use DateTime;
use ReflectionException;
use ReflectionProperty;

/**
 *
 */
class strongType
{
    private array $_missing_properties = [];
    protected static ?array $_alias = null;

    /**
     * @return void
     */
    public function isMissingProperties(): void
    {
        self::checkMissingProperties($this->_missing_properties, static::class);
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
                $code[] = 'public ?string $' . $key . ' = null; // ' . $val;
            }
        }
        Debug([
            implode("\r\n", $code),
            $class . ' missing properties' => $missing_properties,
            'backtrace' => debug_backtrace()
        ]);
    }

    /**
     * @param $name
     */
    public function __get($name)
    {
        $this->_missing_properties[$name] = null;
        self::checkMissingProperties($this->_missing_properties, static::class);
    }

    /**
     * @param $name
     * @param $value
     * @return void
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
    }

    /**
     * @param bool $exclude_empty
     * @return array
     */
    public function toArray(bool $exclude_empty = false): array
    {
        $values = $exclude_empty ? array_filter(get_object_vars($this), static function ($var) {
            return $var !== null;
        }) : get_object_vars($this);
        foreach ($values as $k => $v) {
            if ($k[0] === '_') {
                unset($values[$k]);
                continue;
            }
            if (is_object($v)) {
                if (get_class($v) === DateTime::class) {
                    $values[$k] = Dates::Timestamp($v);
                }
            }
        }
        return $values;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function fromData(array $data): strongType
    {
        foreach ($data as $k => $v) {
            if (is_numeric($k[0])) {
                $k = '_' . $k;
            }

            try {
                $rp = new ReflectionProperty(static::class, $k);
            } catch (ReflectionException $e) {
                $this->$k = $v;
                continue;
            }
            switch ($rp->getType()->getName()) {
                case 'DateTime':
                case 'array':
                case 'string':
                    $this->$k = $v;
                    break;

                case 'float':
                    $this->$k = floatval($v);
                    break;

                case 'int':
                    $this->$k = intval($v);
                    break;

                default:
                    Debug($rp->getType()->getName() . ' unknown type', debug_backtrace());
            }
        }

        self::checkMissingProperties($this->_missing_properties, static::class);

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
        $cols = array_keys(get_object_vars($item));
        foreach ($cols as $i => $col) {
            if ($col[0] === '_') {
                unset($cols[$i]);
                continue;
            }
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
            Debug('QuickDRY Error: Not an array or empty');
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
            Debug('could not open ' . $filename);
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
    public static function toExcel(array $items): ?SimpleExcel
    {
        if (!sizeof($items)) {
            return null;
        }
        $cols = self::getHeaders($items[0]);

        $se = new SimpleExcel();
        $se->Report = $items;
        $se->Title = static::class;
        $se->Columns = [];
        foreach ($cols as $col) {
            $se->Columns[$col] = new SimpleExcel_Column(static::$_alias[$col] ?? null, $col);
        }
        return $se;
    }
}