<?php
declare(strict_types=1);
// put your global constants in here

const SESSION_NAME = 'QuickDRY9';

// REQUIRED: absolute path of an existing, writable folder OUTSIDE the web
// root. QuickDRY writes error logs (and anything your app stores) there,
// and refuses to run with a missing, relative or unwritable path.
// e.g. 'C:/inetpub/myapp-data' or '/var/lib/myapp'
const DATA_FOLDER = '';

const SHOW_ERRORS = 1;

const JS_VERSION = 1;

const MASTER_SECRET_KEY = null;
const URL_SECRET_KEY = null;

const WKHTMLTOPDF = 'path to wkhtmltopdf';