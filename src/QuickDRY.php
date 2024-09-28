<?php
namespace Bkucenski\Quickdry;

class QuickDRY {
    // BasePage
    const string PDF_PAGE_ORIENTATION_LANDSCAPE = 'landscape';
    const string PDF_PAGE_ORIENTATION_PORTRAIT = 'portrait';

// http://doc.qt.io/archives/qt-4.8/qprinter.html#PaperSize-enum
    const string PDF_PAGE_SIZE_A0 = 'A0';
    const string PDF_PAGE_SIZE_A1 = 'A1';
    const string PDF_PAGE_SIZE_A2 = 'A2';
    const string PDF_PAGE_SIZE_A3 = 'A3';
    const string PDF_PAGE_SIZE_A4 = 'A4';
    const string PDF_PAGE_SIZE_A5 = 'A5';
    const string PDF_PAGE_SIZE_A6 = 'A6';
    const string PDF_PAGE_SIZE_A7 = 'A7';
    const string PDF_PAGE_SIZE_A8 = 'A8';
    const string PDF_PAGE_SIZE_A9 = 'A9';

    const string PDF_PAGE_SIZE_B0 = 'B0';
    const string PDF_PAGE_SIZE_B1 = 'B1';
    const string PDF_PAGE_SIZE_B2 = 'B2';
    const string PDF_PAGE_SIZE_B3 = 'B3';
    const string PDF_PAGE_SIZE_B4 = 'B4';
    const string PDF_PAGE_SIZE_B5 = 'B5';
    const string PDF_PAGE_SIZE_B6 = 'B6';
    const string PDF_PAGE_SIZE_B7 = 'B7';
    const string PDF_PAGE_SIZE_B8 = 'B8';
    const string PDF_PAGE_SIZE_B9 = 'B9';
    const string PDF_PAGE_SIZE_B10 = 'B10';

    const string PDF_PAGE_SIZE_C5E = 'C5E';
    const string PDF_PAGE_SIZE_COMM10E = 'Comm10E';
    const string PDF_PAGE_SIZE_DLE = 'DLE';
    const string PDF_PAGE_SIZE_EXECUTIVE = 'Executive';
    const string PDF_PAGE_SIZE_FOLIO = 'Folio';
    const string PDF_PAGE_SIZE_LEDGER = 'Ledger';
    const string PDF_PAGE_SIZE_LEGAL = 'Legal';
    const string PDF_PAGE_SIZE_LETTER = 'Letter';
    const string PDF_PAGE_SIZE_TABLOID = 'Tabloid';

// Web
    const int QUICKDRY_MODE_STATIC = 1;
    const int QUICKDRY_MODE_INSTANCE = 2;
    const int QUICKDRY_MODE_BASIC = 3;

    const string REQUEST_VERB_GET = 'GET';
    const string REQUEST_VERB_POST = 'POST';
    const string REQUEST_VERB_PUT = 'PUT';
    const string REQUEST_VERB_DELETE = 'DELETE';
    const string REQUEST_VERB_HISTORY = 'HISTORY';
    const string REQUEST_VERB_FIND = 'FIND';

    const string REQUEST_EXPORT_CSV = 'CSV';
    const string REQUEST_EXPORT_PDF = 'PDF';
    const string REQUEST_EXPORT_JSON = 'JSON';
    const string REQUEST_EXPORT_DOCX = 'DOCX';
    const string REQUEST_EXPORT_XLS = 'XLS';

// YesNo
    const int SELECT_NO = 1;
    const int SELECT_YES = 2;
}
