<?php
namespace Bkucenski\Quickdry\Web;

use Bkucenski\Quickdry\Connectors\Curl;
use Bkucenski\Quickdry\QuickDRY;
use Bkucenski\Quickdry\Utilities\Debug;
use Bkucenski\Quickdry\Utilities\Log;

class WebKit
{
    public static function Init(Web $Web): void
    {
        if (!$Web->PDFPageOrientation) {
            $Web->PDFPageOrientation = 'Portrait';
        }

        if (strcasecmp($Web->PDFPageOrientation, 'letter') == 0) {
            $Web->PDFPageOrientation = 'portrait';
        }

        $Web->HTML = str_replace('src="/', 'src="' . BASE_URL . '/', $Web->HTML);
        $Web->HTML = str_replace('href="/', 'href="' . BASE_URL . '/', $Web->HTML);
        $Web->HTML = str_replace('src=\'/', 'src=\'' . BASE_URL . '/', $Web->HTML);
        $Web->HTML = str_replace('href=\'/', 'href=\'' . BASE_URL . '/', $Web->HTML);

        $Web->PDFHash = md5($Web->HTML);

        if (!$Web->PDFRootDir) {
            $Web->PDFRootDir = DOC_ROOT_PATH . '/temp';
        }

        if (!is_dir($Web->PDFRootDir)) {
            mkdir($Web->PDFRootDir);
        }

        $html_file = $Web->PDFRootDir . '/' . $Web->PDFHash . '.html';
        $FileName = $html_file . '.pdf';

        if (defined('PDF_API')) {
            $res = Curl::Post(PDF_API, ['html' => urlencode($Web->HTML)]);
            $fp = fopen($FileName, 'w');
            fwrite($fp, $res->Body);
            fclose($fp);
        } else {

            $fp = fopen($html_file, 'w');
            fwrite($fp, $Web->HTML);
            fclose($fp);

            $params = [];
            $params[] = '--javascript-delay 5000';
            $params[] = '--enable-javascript';
            if (!$Web->PDFShrinkToFit) {
                $params[] = '--disable-smart-shrinking';
            } else {
                $params[] = '--enable-smart-shrinking';
            }
            $params[] = '--no-stop-slow-scripts';

            $params[] = match ($Web->PDFPageSize) {
                'brotherql570' => ' --page-width 3.5in --page-height 1.13in --margin-bottom 0 --margin-top 0 --margin-left 0 --margin-right 0',
                'wl-600' => ' --page-width 8.5in --page-height 11.0in --margin-bottom 0.5in --margin-top 0.5in --margin-left 0.18in --margin-right 0.18in',
                default => '--page-size ' . ($Web->PDFPageSize ?: QuickDRY::PDF_PAGE_SIZE_LETTER),
            };

            if ($Web->PDFMargins) {
                $params[] = '-L ' . $Web->PDFMargins->Left . $Web->PDFMargins->Units . ' -R ' . $Web->PDFMargins->Right . $Web->PDFMargins->Units . ' -T ' . $Web->PDFMargins->Top . $Web->PDFMargins->Units . ' -B ' . $Web->PDFMargins->Bottom . $Web->PDFMargins->Units;
            }

// $params[] = '--debug-javascript';
            $params[] = '-O ' . $Web->PDFPageOrientation;
            if ($Web->PDFSimplePageNumbers) {
                $params[] = '--footer-center [page]/[topage]';
            } else {
                if ($Web->PDFHeader) {
                    $params[] = '--header-html "' . $Web->PDFHeader . '"';
                }
                if ($Web->PDFFooter) {
                    $params[] = '--footer-html "' . $Web->PDFFooter . '"';
                }
            }


            $cmd = DOC_ROOT_PATH . '\\QuickDRY\\bin\\wkhtmltopdf.exe ' . implode(' ', $params) . ' ' . $html_file . ' ' . $FileName;
            Log::Insert($cmd);

            $output = [];
            exec($cmd, $output);
            $output = implode(PHP_EOL, $output);


            $e = error_get_last();
            if (!is_null($e) && !stristr($e['message'], 'statically')) {
                Debug::Halt($e);
            }


            if (!file_exists($FileName)) {
                Debug::Halt(['file not created', 'file' => $FileName, 'cmd' => $cmd, 'output' => $output]);
            }

            if ($Web->PDFPostFunction) {
                call_user_func($Web->PDFPostFunction);
            }

            if ($Web->PDFPostRedirect) {
                header('location: ' . $Web->PDFPostRedirect);
                unset($Web->PDFPostRedirect);
                exit();
            }
        }

        if (isset($_SERVER['HTTP_HOST'])) {
            header('Content-type: application/pdf');
            header('Content-Disposition: inline; filename="' . $Web->PDFFileName . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            readfile($FileName);
        } else {
            rename($FileName, $Web->PDFFileName);
        }
    }
}
