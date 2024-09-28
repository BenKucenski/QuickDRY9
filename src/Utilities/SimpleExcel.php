<?php

namespace Bkucenski\Quickdry\Utilities;

use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Class SimpleExcel
 *
 * @property string Filename
 * @property string Title
 * @property SimpleReport[] Report
 * @property SimpleExcel_Column[] Columns
 */
class SimpleExcel extends strongType
{
    public string $Filename;
    public array $Report;
    public array $Columns;
    public string $Title;

    /**
     * @param Worksheet $sheet
     */
    private static function SetDefaultSecurity(Worksheet $sheet): void
    {
        // when marking a sheet protected, there are a number of settings that should not be set by default
        $protection = $sheet->getProtection();
        $protection->setSelectUnlockedCells(false);
        $protection->setSelectLockedCells(false);
        $protection->setFormatCells(true);
        $protection->setFormatColumns(true);
        $protection->setFormatRows(true);
        $protection->setInsertColumns(true);
        $protection->setInsertHyperlinks(true);
        $protection->setInsertRows(true);
        $protection->setDeleteColumns(true);
        $protection->setDeleteRows(true);

    }

    /**
     * @param SimpleExcel $se
     * @param bool $SafeMode
     *
     * Safe Mode means that the values are cleaned up of any characters not found
     * on a standard US keyboard
     */
    public static function ExportSpreadsheet(SimpleExcel $se, bool $SafeMode = false): void
    {
        if (!$se->Filename) {
            Debug('QuickDRY Error: Filename required');
        }
        $se->Title = $se->Title ? substr($se->Title, 0, 31) : 'Sheet'; // max 31 characters
        $parts = pathinfo($se->Filename);
        if (!isset($parts['extension']) || strcasecmp($parts['extension'], 'xlsx') !== 0) {
            $se->Filename .= '.xlsx';
        }

        $sheet = null;
        $spreadsheet = new Spreadsheet();
        try {
            $sheet = $spreadsheet->getActiveSheet();
        } catch (Exception $ex) {
            Debug::Halt($ex);
        }
        self::SetDefaultSecurity($sheet);
        $sheet->setTitle($se->Title);
        $sheet_row = 1;
        $sheet_column = 'A';
        foreach ($se->Columns as $column) {
            self::_SetSpreadsheetCellValue($sheet, $sheet_column, $sheet_row, $column->Header, $column->PropertyType);
            $sheet_column++;
        }
        $sheet_row++;
        foreach ($se->Report as $item) {
            if (!is_object($item)) {
                Debug($item);
            }

            $is_std = get_class($item) === 'stdClass';

            $sheet_column = 'A';
            foreach ($se->Columns as $column) {
                try { // need to use try catch so that magic __get columns are accessible
                    if (!$is_std) { // if the class type is not a stdClass, then let the class type handle errors
                        $value = $SafeMode ? Strings::KeyboardOnly($item->{$column->Property}) : $item->{$column->Property};
                    } elseif (isset($item->{$column->Property})) { // otherwise, check to see if properties exist or set the value to an empty string
                        $value = $SafeMode ? Strings::KeyboardOnly($item->{$column->Property}) : $item->{$column->Property};
                    } else {
                        $value = '';
                    }

                } catch (Exception $ex) {
                    $value = '';
                }

                self::_SetSpreadsheetCellValue($sheet, $sheet_column, $sheet_row, $value, $column->PropertyType);
                $sheet_column++;
            }
            $sheet_row++;
        }


        try {
            $writer = new Xlsx($spreadsheet);
            if (isset($_SERVER['HTTP_HOST'])) {
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="' . $se->Filename . '"');
                header('Cache-Control: max-age=0');
                $writer->save('php://output');
                exit;
            } else {
                $writer->save($se->Filename);
            }
        } catch (Exception $ex) {
            Debug::Halt($ex);
        }

    }

    /**
     * @param string $filename
     * @param SimpleExcel[] $ses
     * @param bool $exit_on_error
     * @param bool $SafeMode
     * @throws Exception
     */
    public static function ExportSpreadsheets(
        string $filename,
        array  $ses,
        bool   $exit_on_error = true,
        bool   $SafeMode = false
    ): void
    {
        $spreadsheet = new Spreadsheet();

        $total_sheets = sizeof($ses);

        foreach ($ses as $sheet => $report) {
            if (!isset($_SERVER['HTTP_HOST'])) {
                Log::Insert(($sheet + 1) . ' / ' . $total_sheets . ' : ' . $report->Title);
            }
            if ($sheet > 0) {
                try {
                    $spreadsheet->createSheet($sheet);
                } catch (Exception $ex) {
                    Debug($ex);
                }
            }
            try {
                $spreadsheet->setActiveSheetIndex($sheet);
            } catch (Exception $ex) {
                Debug::Halt($ex);
            }
            $xls_sheet = null;
            try {
                $xls_sheet = $spreadsheet->getActiveSheet();
                $xls_sheet->setTitle($report->Title ?: 'Sheet ' . ($sheet + 1));
            } catch (Exception $ex) {
                Debug($ex);
            }
            self::SetDefaultSecurity($xls_sheet);

            $sheet_row = 1;

            $sheet_column = 'A';
            foreach ($report->Columns as $column) {
                self::_SetSpreadsheetCellValue($xls_sheet, $sheet_column, $sheet_row, $column->Header, $column->PropertyType);
                $sheet_column++;
            }
            $sheet_row++;
            if ($report->Report && is_array($report->Report)) {
                // $m = sizeof($report->Report);
                foreach ($report->Report as $item) {
                    if (!is_object($item)) {
                        Debug($item);
                    }

                    $is_std = get_class($item) === 'stdClass';

                    $sheet_column = 'A';
                    foreach ($report->Columns as $column) {
                        try { // need to use try catch so that magic __get columns are accessible
                            if (!$is_std) { // if we're not using a stdClass, then let the class type handle errors
                                $value = $item->{$column->Property};
                            } else { // otherwise, use an empty string for non-existent properties
                                $value = $item->{$column->Property} ?? '';
                            }
                        } catch (Exception $ex) {
                            $value = '';
                        }
                        if (!is_object($value) && $SafeMode) {
                            $value = Strings::KeyboardOnly($value);
                        }
                        self::_SetSpreadsheetCellValue($xls_sheet, $sheet_column, $sheet_row, $value, $column->PropertyType);
                        $sheet_column++;
                    }
                    $sheet_row++;
                }
            }
        }


        try {
            $writer = new Xlsx($spreadsheet);
            if (isset($_SERVER['HTTP_HOST'])) {
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="' . $filename . '"');
                header('Cache-Control: max-age=0');
                $writer->save('php://output');
                exit;
            } else {
                $writer->save($filename);
            }
        } catch (Exception $ex) {
            if ($exit_on_error) {
                Debug::Halt($ex);
            }
            throw new Exception($ex);
        }

    }

    /**
     * @param SimpleExcel $se
     * @param string $delimiter
     */
    public static function ExportCSV(SimpleExcel $se, string $delimiter = ','): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = null;
        try {
            $sheet = $spreadsheet->getActiveSheet();
        } catch (Exception $ex) {
            Debug::Halt($ex);
        }
        $sheet->setTitle($se->Title);
        $sheet_row = 1;
        $sheet_column = 'A';
        foreach ($se->Columns as $column) {
            self::_SetSpreadsheetCellValue($sheet, $sheet_column, $sheet_row, $column->Header, $column->PropertyType);
            $sheet_column++;
        }
        $sheet_row++;
        foreach ($se->Report as $item) {
            $sheet_column = 'A';
            foreach ($se->Columns as $column) {
                self::_SetSpreadsheetCellValue($sheet, $sheet_column, $sheet_row, $item->{$column->Property}, $column->PropertyType);
                $sheet_column++;
            }
            $sheet_row++;
        }


        try {
            $writer = new Csv($spreadsheet);
            $writer->setDelimiter($delimiter);
            if (isset($_SERVER['HTTP_HOST'])) {
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment;filename="' . $se->Filename . '"');
                header('Cache-Control: max-age=0');
                $writer->save('php://output');
                exit;
            } else {
                $writer->save($se->Filename);
            }
        } catch (Exception $ex) {
            Debug::Halt($ex);
        }

    }


    /**
     * @param Worksheet $sheet
     * @param $sheet_column
     * @param $sheet_row
     * @param $value
     * @param int $property_type
     */
    private static function _SetSpreadsheetCellValue(
        Worksheet $sheet,
                  $sheet_column,
                  $sheet_row,
                  $value,
        int       $property_type = 0): void
    {
        if (!$value) {
            return;
        }

        if ($value instanceof DateTime) {
            $value = $property_type == SimpleExcel_Column::SIMPLE_EXCEL_PROPERTY_TYPE_DATE ? Dates::Datestamp($value, '') : Dates::Timestamp($value, '');
            $value = Date::PHPToExcel( $value );
        }

        if ($property_type == SimpleExcel_Column::SIMPLE_EXCEL_PROPERTY_TYPE_AS_GIVEN) {
            $sheet->setCellValueExplicit($sheet_column . $sheet_row, $value, DataType::TYPE_STRING);
        } else {
            if ($property_type == SimpleExcel_Column::SIMPLE_EXCEL_PROPERTY_TYPE_DATE) {
                try {
                    $sheet
                        ->getStyle($sheet_column . $sheet_row)
                        ->getNumberFormat()
                        ->setFormatCode(
                            NumberFormat::FORMAT_DATE_YYYYMMDD
                        );
                } catch (Exception $ex) {
                    Debug::Halt($ex);
                }
            }
            if ($property_type == SimpleExcel_Column::SIMPLE_EXCEL_PROPERTY_TYPE_CURRENCY) {
                try {
                    $sheet
                        ->getStyle($sheet_column . $sheet_row)
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                } catch (Exception $ex) {
                    Debug::Halt($ex);
                }
            }

            if ($value && $property_type == SimpleExcel_Column::SIMPLE_EXCEL_PROPERTY_TYPE_HYPERLINK) {
                // don't try to set a URL if the URL is an empty value, it throws an exception
                try {
                    $sheet->getCell($sheet_column . $sheet_row)
                        ->getHyperlink()
                        ->setUrl($value);
                } catch (Exception $ex) {
                    Debug::Halt($ex);
                }
            }

            if (is_array($value)) {
                Debug::Halt(['value cannot be an array', $value]);
            }
            try {
                $sheet->setCellValue($sheet_column . $sheet_row, $value);
            } catch (Exception $ex) {
                Debug($ex);
            }
        }
    }
}