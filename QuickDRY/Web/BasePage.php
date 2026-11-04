<?php
declare(strict_types=1);

namespace QuickDRY\Web;

use QuickDRY\Utilities\strongType;

/**
 * Class BasePage
 *
 */
class BasePage extends strongType
{
    public static ?string $PDFPageOrientation = null;
    public static ?string $PDFPageSize = null;
    public static ?bool $PDFShrinkToFit = null;
    public static ?string $PDFFileName = null;
    public static ?PDFMargins $PDFMargins = null;
    public static ?string $PDFHeader = null;
    public static ?string $PDFFooter = null;
    public static ?string $DOCXPageOrientation = null;
    public static ?string $DOCXFileName = null;
    public static ?string $PDFPostRedirect = null;
    public static ?bool $IncludeMenu = null;
    public static ?array $PostData = null;
    protected static ?array $Errors = [];
    public static ?string $MasterPage = null;
    private static ?string $MetaTitle = null;
    public static ?string $CurrentPage = null;
    public static ?array $PutData = null;

    public static ?string $Notice = null;
    public static ?string $Error = null;

    /**
     * @return void
     */
    public static function Get(): void
    {

    }

    /**
     * @return void
     */
    public static function Post(): void
    {

    }


    /**
     * @return string|null
     */
    public static function getMasterPage(): ?string
    {
        return self::$MasterPage;
    }

    /**
     * @param string $masterpage
     * @return void
     */
    public static function setMasterPage(string $masterpage): void
    {
        self::$MasterPage = $masterpage;
    }

    /**
     * @param string $value
     * @return void
     */
    public static function setPDFOrientation(string $value): void
    {
        self::$PDFPageOrientation = $value;
    }

    /**
     * @param string $value
     * @return void
     */
    public static function setPDFFilename(string $value): void
    {
        self::$PDFFileName = $value;
    }

    /**
     * @return string|null
     */
    public static function getMetaTitle(): ?string
    {
        return self::$MetaTitle;
    }

    /**
     * @param string $metaTitle
     * @return void
     */
    public static function setMetaTitle(string $metaTitle): void
    {
        self::$MetaTitle = $metaTitle;
        Meta::setTitle($metaTitle);
    }


    /**
     * @return void
     */
    public static function Init(): void
    {
        $temp = parse_url($_SERVER['REQUEST_URI'] ?? '');
        self::$CurrentPage = $temp['path'] ?? '/';

        if (self::$Notice = Session::Get('notice')) {
            Session::Set('notice', null);
        }

        if (self::$Error = Session::Get('error')) {
            Session::Set('error', null);
        }

        $input = file_get_contents('php://input');
        if($input) {
            $putData = [];
            $json = json_decode($input, true);

            if (json_last_error()) {
                parse_str($input, $putData);
            } else {
                $putData = $json;
            }

            self::$PostData = $putData;
            foreach ($putData as $name => $value) {
                $_POST[$name] = $value;
                $_REQUEST[$name] = $value;
            }
        }
    }

    /**
     * @return void
     */
    public static function Put()
    {

    }

    /**
     * @return void
     */
    public static function Patch()
    {

    }

    /**
     * @return void
     */
    public static function Options()
    {

    }

    /**
     * @return void
     */
    public static function Delete()
    {

    }

    /**
     * @return void
     */
    public static function Find()
    {

    }

    /**
     * @return void
     */
    public static function History()
    {

    }

    /**
     * @return string
     */
    public static function GetClassName(): string
    {
        return get_called_class();
    }

    /**
     * @return void
     */
    public static function ExportToXLS(): void
    {
        Exception('QuickDRY Error: ExportToXLS is not implemented');
    }

    /**
     * @return void
     */
    public static function ExportToPDF(): void
    {
        Exception('QuickDRY Error: ExportToPDF is not implemented');
    }

    /**
     * @return void
     */
    public static function ExportToDOCX(): void
    {
        Exception('QuickDRY Error: ExportToDOCX is not implemented');
    }

    /**
     * @return void
     */
    public static function ExportToCSV(): void
    {
        Exception('QuickDRY Error: ExportToCSV is not implemented');
    }

    /**
     * @return void
     */
    public static function ExportToJSON(): void
    {
        Exception('QuickDRY Error: ExportToJSON is not implemented');
    }
}