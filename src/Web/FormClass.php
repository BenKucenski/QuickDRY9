<?php

namespace Bkucenski\Quickdry\Web;

/**
 * Class FormClass
 */
class FormClass
{
    public static array $_options = [];

    /**
     * @return array
     */
    public static function Options(): array
    {
        return static::$_options;
    }

    /**
     * @param $id
     *
     * @return string|null
     */
    public static function Get($id): ?string
    {
        return static::$_options[$id] ?? null;
    }

    /**
     * @param array $options
     * @param string|null $selected
     * @param ElementID $elementID
     * @param string $class
     * @param string $onchange
     * @param bool $add_none
     * @return string
     */
    public static function SelectItems(
        array     $options,
        ?string   $selected,
        ElementID $elementID,
        string    $class = '',
        string    $onchange = '',
        bool      $add_none = false): string
    {
        $name = $elementID->name;
        $id = $elementID->id;

        $res = '<select onchange="' . $onchange . '" class="' . $class . '" name="' . $name . '" id="' . $id . '">';
        if ($add_none) {
            $res .= '<option value="null">' . (is_bool($add_none) ? 'Select One...' : $add_none) . '</input>';
        }
        foreach ($options as $id => $disp) {
            if ($id == $selected) {
                $res .= '<option selected value="' . $id . '">' . $disp . '</input>';
            } else {
                $res .= '<option value="' . $id . '">' . $disp . '</input>';
            }
        }
        $res .= '</select>';

        return $res;

    }

    /**
     * @param array $options
     * @param array|null $selected
     * @param ElementID $elementID
     * @param string $class
     * @param string $onchange
     * @param bool $add_none
     * @return string
     */
    public static function SelectMultiItems(
        array     $options,
        ?array    $selected,
        ElementID $elementID,
        string    $class = '',
        string    $onchange = '',
        bool      $add_none = false): string
    {
        $res = '<select onchange="' . $onchange . '" class="' . $class . '" name="' . $elementID->name . '" id="' . $elementID->id . '">';
        if ($add_none) {
            $res .= '<option value="null">' . (is_bool($add_none) ? 'Select One...' : $add_none) . '</input>';
        }
        foreach ($options as $id => $disp) {
            if (in_array($id, $selected)) {
                $res .= '<option selected value="' . $id . '">' . $disp . '</input>';
            } else {
                $res .= '<option value="' . $id . '">' . $disp . '</input>';
            }
        }
        $res .= '</select>';

        return $res;

    }

    /**
     * @param string $val
     * @param ElementID $elementID
     * @param string|null $outer_style
     * @param string|null $inner_style
     * @return string
     */
    public static function Textarea(
        string    $val,
        ElementID $elementID,
        string    $outer_style = null,
        string    $inner_style = null): string
    {
        $name = $elementID->name;
        $id = $elementID->id;

        return
            '<div id="' . $id . '_div" style="' . $outer_style . '"><textarea style="' . $inner_style . '" name="' . $name . '" id="' . $id
            . '">' . $val . '</textarea></div>';
    }

    /**
     * @param string $val
     * @param ElementID $elementID
     * @param string|null $outer_style
     * @param string|null $inner_style
     * @return string
     */
    public static function Text(
        string    $val,
        ElementID $elementID,
        string    $outer_style = null,
        string    $inner_style = null): string
    {
        $name = $elementID->name;
        $id = $elementID->id;

        return '<div id="' . $id . '_div" style="' . $outer_style . '"><input type="text" style="' . $inner_style . '" name="' . $name
            . '" id="' . $id . '" value="' . $val . '" /></div>';
    }

    /**
     * @param array $selected
     * @param array $options
     * @param ElementID $elementID
     * @param string|null $outer_class
     * @param string|null $inner_style
     * @param bool $new_line
     * @param string $onchange
     * @return string
     */
    public static function Checkbox(
        array     $selected,
        array     $options,
        ElementID $elementID,
        string    $outer_class = null,
        string    $inner_style = null,
        bool      $new_line = false,
        string    $onchange = ''): string
    {
        $name = $elementID->name;
        $id = $elementID->id;

        if ($name) {
            $name .= '[]';
        }

        $res = '<div id="' . $id . '_div" class="' . $outer_class . '">';
        foreach ($options as $i => $option) {
            if (in_array($option, $selected)) {
                $res .= '<label><input class="' . $id . '_checked" checked="checked" onchange="' . $onchange . '" type="checkbox" style="' . $inner_style . '" name="' . $name . '" id="' . $id . '_' . $i
                    . '" value="' . $option . '" />' . $option . '</label>';
            } else {
                $res .= '<label><input class="' . $id . '_checked" type="checkbox" onchange="' . $onchange . '" style="' . $inner_style . '" name="' . $name . '" id="' . $id . '_' . $i . '" value="'
                    . $option . '" />' . $option . '</label>';
            }
            if ($new_line) {
                $res .= '<br/>';
            }
        }

        return $res . '</div>';
    }

    /**
     * @param string $selected
     * @param array $options
     * @param ElementID $elementID
     * @param string|null $outer_style
     * @param string|null $inner_style
     * @param bool $new_line
     * @return string
     */
    public static function Radio(
        string    $selected,
        array     $options,
        ElementID $elementID,
        string    $outer_style = null,
        string    $inner_style = null,
        bool      $new_line = false): string
    {
        $name = $elementID->name;
        $id = $elementID->id;

        $res = '<div id="' . $id . '_div" style="' . $outer_style . '">';
        foreach ($options as $option => $display) {
            if ($option == $selected) {
                $res .= '<input checked="checked" type="radio" style="' . $inner_style . '" name="' . $name . '" id="' . $id . '_' . $option
                    . '" value="' . $option . '" />' . $display;
            } else {
                $res .=
                    '<input type="radio" style="' . $inner_style . '" name="' . $name . '" id="' . $id . '_' . $option . '" value="' . $option
                    . '" />' . $display;
            }
            if ($new_line) {
                $res .= '<br/>';
            }
        }

        return $res . '</div>';
    }
}