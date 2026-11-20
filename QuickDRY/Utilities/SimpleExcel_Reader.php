<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RuntimeException;
use Throwable;

/**
 * Class SimpleExcel_Reader
 */
class SimpleExcel_Reader extends strongType
{
    /**
     * @param string $file
     * @param bool $process_cells
     * @param bool $debug
     * @param int|null $row_limit
     * @return array
     * @throws RuntimeException when the file can't be loaded or read (the original error is getPrevious())
     */
    public static function FromFilename(string $file, bool $process_cells = true, bool $debug = false, ?int $row_limit = null): array
    {
        if ($debug) {
            Log::Insert('SimpleExcel_Reader::FromFilename');
        }
        try {
            if ($debug) {
                Log::Insert('Loading');
            }
            $spreadsheet = IOFactory::load($file);
            if ($debug) {
                Log::Insert('Done Loading');
            }
        } catch (Throwable $e) {
            throw new RuntimeException('Error loading file "' . $file . '": ' . $e->getMessage(), 0, $e);
        }

        return self::ToReport($spreadsheet, $process_cells, $debug, $row_limit);
    }

    /**
     * @param Spreadsheet $spreadsheet
     * @param bool $process_cells
     * @param bool $debug
     * @param int|null $row_limit
     * @return array
     * @throws RuntimeException when a sheet can't be read (the original error is getPrevious())
     */
    public static function ToReport(Spreadsheet $spreadsheet, bool $process_cells = true, bool $debug = false, ?int $row_limit = null): array
    {
        if ($debug) {
            Log::Insert('SimpleExcel_Reader::ToReport');
        }
        $report = [];
        $sheetCount = $spreadsheet->getSheetCount();
        $sheetNames = $spreadsheet->getSheetNames();

        if ($debug) {
            Log::Insert('Sheet Count: ' . $sheetCount);
        }

        for ($sheet = 0; $sheet < $sheetCount; $sheet++) {
            try {
                if ($debug) {
                    Log::Insert('Sheet: ' . $sheetNames[$sheet]);
                }

                $activeSheet = $spreadsheet->setActiveSheetIndex($sheet);

                $report[$sheetNames[$sheet]] = [];

                $rows = $activeSheet->getHighestRow();
                $cols = $activeSheet->getHighestColumn();

                if ($debug) {
                    Log::Insert('Rows: ' . $rows . ', Cols: ' . $cols);
                }

                $per_page = 100;
                for ($row = 1; $row <= $rows; $row += $per_page) {
                    if ($debug) {
                        Log::Insert($row . ' / ' . $rows);
                    }
                    $end = ($row + $per_page - 1);
                    if ($end > $rows) {
                        $end = $rows;
                    }
                    if ($row_limit && $row > $row_limit) {
                        break;
                    }
                    $data = $activeSheet->rangeToArray('A' . $row . ':' . $cols . ($end),
                        NULL,
                        $process_cells,
                        $process_cells);

                    foreach ($data as $record) {
                        $report[$sheetNames[$sheet]] [] = $record;
                    }
                }

                if ($debug) {
                    Log::Insert('Done Reading Data');
                }

            } catch (Throwable $ex) {
                throw new RuntimeException('Error reading sheet "' . $sheetNames[$sheet] . '": ' . $ex->getMessage(), 0, $ex);
            }
        }
        return $report;
    }
}