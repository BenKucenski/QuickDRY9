<?php
declare(strict_types=1);

namespace QuickDRY\Web;

use QuickDRY\Connectors\mssql\MSSQL_Connection;
use QuickDRY\Connectors\mysql\MySQL_Connection;
use QuickDRY\Utilities\HTTP;
use QuickDRY\Utilities\Navigation;
use QuickDRY\Utilities\strongType;

/**
 * Class Web
 *
*/
class Web extends strongType
{
    public ?string $Namespace = null;
    public ?string $ControllerFile = null;
    public ?string $ViewFile = null;
    public ?string $PageClass = null;
    public ?bool $IsJSON = null;
    public ?string $MasterPage = null;
    public ?string $SettingsFile = null;
    public string $CurrentPage;
    public string $CurrentPageName;
    public ?string $IndexFile = null;
    public ?string $ScriptDir = null; // application root, set by Init()

    private ?array $SecureMasterPages = null;

    public ?bool $RenderPDF = null;
    public ?string $PDFPageOrientation = null;
    public ?string $PDFPageSize = null;
    public ?string $PDFFileName = null;
    public ?string $PDFPostRedirect = null;
    public ?string $PDFHeader = null;
    public ?string $PDFFooter = null;
    public ?string $PDFSimplePageNumbers = null;
    public ?PDFMargins $PDFMargins = null;

    /* @var callable $PDFPostFunction */
    public $PDFPostFunction;

    public ?string $PDFHash = null;
    public ?string $PDFRootDir = null;
    public ?bool $PDFShrinkToFit = null;

    public ?string $HTML = null;
    public ?string $js = null;
    public ?string $css = null;
    public ?string $mjs = null; // null


    public ?string $Verb = null;
    public ?int $StartTime = null;
    public ?int $InitTime = null;

    /**
     * Require the session's CSRF token (Csrf::Token()) on every request that can change data.
     * Pages must send it: put Csrf::MetaTag() in the <head> and load /QuickDRY/js/QuickDRY.Csrf.js
     * after jQuery. Off by default so existing projects keep working; turn it on in index.php.
     */
    public bool $RequireCSRFToken = false;

    /**
     * Pages the signed-in user (or, signed out, the public) may open. Set it with SetNavigation()
     * before Exec(); see EnforceAccess() for how it's used.
     */
    public ?Navigation $AccessNavigation = null;

    /** Where signed-out visitors are sent; ?continue= holds the page they asked for. Default: DefaultPage. */
    public ?string $SignInPage = null;

    /**
     * Generated pages (QuickDRY's code generator) that are never public unless the navigation
     * allows the page for signed-out visitors or it's under PublicPaths.
     * @var string[]
     */
    public array $ProtectedPaths = ['/json', '/manage'];

    /**
     * When there's a navigation (SetNavigation), pages here need to be allowed by it, signed in or
     * not, like the legacy framework did. Generated /manage pages list whole tables.
     * @var string[]
     */
    public array $NavigationRequiredPaths = ['/manage'];

    /**
     * Paths that skip the access check entirely, e.g. ['/api'] for endpoints that authenticate
     * with their own keys.
     * @var string[]
     */
    public array $PublicPaths = [];

    /** Turn the access check off (not recommended). */
    public bool $AccessCheckEnabled = true;

    /** @var callable|null returns true when someone is signed in; default asks models\CurrentUser */
    private $SignedInCheck = null;

    public ?string $DefaultPage = null;
    public ?string $DefaultUserPage = null;

    public ?string $MetaTitle = null;
    public ?string $MetaDescription = null;
    public ?string $MetaKeywords = null;
    public ?bool $NoCache = null;

    /**
     * @param string[] $MasterPages
     */
    public function SetSecureMasterPages(array $MasterPages): void
    {
        $this->SecureMasterPages = $MasterPages;
    }

    /**
     * @return bool
     */
    public function IsSecureMasterPage(): bool
    {
        if (!is_array($this->SecureMasterPages)) {
            return false;
        }

        return in_array($this->MasterPage, $this->SecureMasterPages);
    }

    public function __construct()
    {
        $this->StartTime = time();
        $this->RenderPDF = false;
        
        if (isset($_SERVER['REQUEST_URI'])) {
            if (!defined('HTTP_HOST')) {
                if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
                    $host = explode(',', $_SERVER['HTTP_X_FORWARDED_HOST']);
                    $host = trim($host[sizeof($host) - 1]);
                } else {
                    $host = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : ($_HOST ?? '');
                }

                define('HTTP_HOST', strtolower($host)); // the domain that the site needs to behave as (for proxies)
            }
        }

        if(!defined('HTTP_HOST')) {
            define('HTTP_HOST', 'command line');
        }

        if (defined('HTTP_HOST')) {
            $this->SettingsFile = 'settings.' . HTTP_HOST . '.php';
        }

        if (defined('MYSQL_LOG') && MYSQL_LOG) {
            MySQL_Connection::$use_log = true;
        }

        if (defined('MSSQL_LOG') && MSSQL_LOG) {
            MSSQL_Connection::$use_log = true;
        }
    }

    /**
     * @param string $default_page
     * @param string $default_user_page
     * @param string $script_dir
     */
    public function Init(
        string $default_page,
        string $default_user_page,
        string $script_dir
    ): void
    {
        $this->DefaultPage = $default_page;
        $this->DefaultUserPage = $default_user_page;
        $this->ScriptDir = rtrim($script_dir, '/\\');

        $t = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] ? $_SERVER['DOCUMENT_ROOT'] : $script_dir;
        if ($t[strlen($t) - 1] == '/') {
            $t = substr($t, 0, strlen($t) - 1);
        }
        define('DOC_ROOT_PATH', $t);

        define('SORT_BY', $_REQUEST['sort_by'] ?? null);
        define('SORT_DIR', $_REQUEST['sort_dir'] ?? 'asc');

        define('PAGE', (int)($_REQUEST['page'] ?? 0));
        define('PER_PAGE', (int)($_REQUEST['per_page'] ?? 20));


        $qs = $_SERVER['QUERY_STRING'] ?? null;
        $ru = $_SERVER['REQUEST_URI'] ?? '';

        $url = strtok($ru, '?');

        $_SESSION['last_url'] = $url;

        define('JSON_REQUEST', stristr($ru, '.json') !== false);

        $page = str_replace('?' . $qs, '', $ru);
        $page = str_replace('/' . $qs, '/', $page);

        if(!$page) {
            $page = '/';
        }

        if ($page[strlen($page) - 1] == '/') {
            $page = substr($page, 0, strlen($page) - 1);
        }

        $page = str_replace('-', '_', $page);

        $full_path = $page != '/' ? $page : '/';
        $t = explode('/', $full_path);
        $cur_page = $t[sizeof($t) - 1];

        if (!$cur_page) {
            $cur_page = $this->DefaultPage;
            $full_path = $cur_page;
            $cur_page = explode('/', $cur_page);
            $cur_page = $cur_page[sizeof($cur_page) - 1];
        }

        $host = explode('.', HTTP_HOST);
        $m = sizeof($host);

        if (sizeof($host) >= 2) {
            define('URL_DOMAIN', $host[$m - 2] . '.' . $host[$m - 1]);
        } else {
            define('URL_DOMAIN', $host[0]);
        }

        define('COOKIE_DOMAIN', '.' . URL_DOMAIN);

        define('CURRENT_PAGE', $full_path);
        define('CURRENT_PAGE_NAME', $cur_page);

        $this->CurrentPage = $full_path;
        $this->CurrentPageName = $cur_page;

        if(file_exists('pages' . $this->CurrentPage)) {
            if(str_ends_with($this->CurrentPage, '.html')) {
                exit(file_get_contents('pages' . $this->CurrentPage));
            }
        }

        $page_alt = 'pages' . $this->CurrentPage . '/' . $this->CurrentPageName . '.html.php';
        $code_alt = 'pages' . $this->CurrentPage . '/' . $this->CurrentPageName . '.php';
        $js_alt = 'pages' . $this->CurrentPage . '/' . $this->CurrentPageName . '.js';
        $css_alt = 'pages' . $this->CurrentPage . '/' . $this->CurrentPageName . '.css';

        $this->Namespace = str_replace('/', '\\', 'pages' . $this->CurrentPage);


        $this->ControllerFile = (file_exists($code_alt) ? $code_alt : null);
        $this->ViewFile = (file_exists($page_alt) ? $page_alt : null);
        $this->css = (file_exists($css_alt) ? $css_alt : null);
        $this->js = (file_exists($js_alt) ? $js_alt : null);


        // Accept page.json.php and json.page.php
        $this->IsJSON = false;
        if (stristr($this->CurrentPageName, '.html') !== false) {
            $this->ControllerFile = $this->ViewFile;
            $this->ViewFile = null;
            $this->IsJSON = false;
        } elseif (stristr($this->CurrentPageName, '.json') !== false) {
            $this->ControllerFile = $this->ViewFile;
            $this->ViewFile = null;
            $this->IsJSON = true;
        } elseif (stristr($this->CurrentPageName, 'json.') !== false) {
            $this->ControllerFile = $this->ViewFile;
            $this->ViewFile = null;
            $this->IsJSON = true;
        } elseif (stristr($this->CurrentPageName, '.xlsx') !== false) {
            $this->ControllerFile = $this->ViewFile;
            $this->ViewFile = null;
            $this->IsJSON = true;
        } elseif (stristr($this->CurrentPageName, '.pdf') !== false) {
            $this->ControllerFile = $this->ViewFile;
            $this->ViewFile = null;
            $this->IsJSON = true;
        }


        $temp = explode('.', $this->CurrentPageName);
        $this->PageClass = $this->Namespace . '\\' . $temp[0];

        $this->Verb = self::ResolveVerb($_SERVER['REQUEST_METHOD'] ?? 'GET', $_REQUEST['verb'] ?? null);

        $this->SetURLs();
    }

    /** Verbs that only read. A GET request may use ?verb= to pick one of these, never a write. */
    public const array READ_VERBS = ['GET', 'FIND', 'HISTORY'];

    /**
     * The handler to run: the HTTP method, or the request's "verb" parameter (QuickDRY.js sends
     * POST with verb=PUT/POST/DELETE/GET). On a GET or HEAD request the parameter can only choose
     * a read-only verb, so a link or <img src> can't delete or save anything.
     *
     * @param string $method REQUEST_METHOD
     * @param mixed $verb the request's "verb" parameter, if any
     * @return string
     */
    public static function ResolveVerb(string $method, mixed $verb): string
    {
        $method = strtoupper($method);
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        $verb = is_string($verb) && $verb !== '' ? strtoupper($verb) : $method;

        if ($method === 'GET' && !in_array($verb, self::READ_VERBS, true)) {
            return 'GET';
        }
        return $verb;
    }

    /**
     * @return void
     */
    public function SetURLs(): void
    {
        // this must be done after the settings file is loaded to support proxy situations
        define('FULL_URL', (HTTP::IsSecure() ? 'https://' : 'http://') . HTTP_HOST . ($_SERVER['REQUEST_URI'] ?? '/'));

        if (isset($_SERVER['HTTPS'])) { // check if page being accessed by browser
            $protocol = HTTP::IsSecure() ? 'https://' : 'http://';

            if (!HTTP::IsSecure() && defined('FORCE_SSL') && FORCE_SSL) {
                HTTP::Redirect('https://' . HTTP_HOST);
            }

            define('BASE_URL', $protocol . HTTP_HOST);
        } else {
            if (isset($_SERVER['HTTP_HOST'])) {
                if (defined('FORCE_SSL') && FORCE_SSL) {
                    HTTP::Redirect('https://' . HTTP_HOST);
                }
            }
            if (!defined('BASE_URL')) { // allows the secure URL to be set in scheduled tasks
                define('BASE_URL', (defined('HTTP_HOST_IS_SECURE') && HTTP_HOST_IS_SECURE ? 'https://' : 'http://') . HTTP_HOST);
            }
        }
    }

    /**
     * The pages this visitor may open: build it from the signed-in user's roles (or the signed-out
     * role) before Exec(), e.g. $web->SetNavigation(Menu::ForRoles(CurrentUser::Roles()));
     */
    public function SetNavigation(?Navigation $navigation): void
    {
        $this->AccessNavigation = $navigation;
    }

    /**
     * How Web tells whether someone is signed in. Default: models\CurrentUser::ID() (or its
     * static $id), the convention generated code uses.
     */
    public function SetSignedInCheck(?callable $check): void
    {
        $this->SignedInCheck = $check;
    }

    /**
     * @return bool
     */
    public function IsSignedIn(): bool
    {
        if ($this->SignedInCheck) {
            return (bool)($this->SignedInCheck)();
        }
        $class = 'models\\CurrentUser';
        if (class_exists($class)) {
            if (method_exists($class, 'ID')) {
                return (bool)$class::ID();
            }
            if (property_exists($class, 'id')) {
                return (bool)$class::$id;
            }
        }
        return false; // can't tell: treat as signed out
    }

    /**
     * @param string $page
     * @param string[] $prefixes
     * @return bool
     */
    public static function PathStartsWith(string $page, array $prefixes): bool
    {
        $page = Navigation::NormalizeURL($page);
        foreach ($prefixes as $prefix) {
            $prefix = Navigation::NormalizeURL($prefix);
            if ($prefix === '/' || $page === $prefix || str_starts_with($page, $prefix . '/')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Who may open the requested page. Called by Exec(): the path rules before the page's Init(),
     * the master page rule after it (Init() picks the master page), so keep work that changes
     * data out of Init().
     *
     *   1. PublicPaths: anyone.
     *   2. Allowed by the navigation (SetNavigation): yes. This is how a /json or /manage page is
     *      made public: allow it for the signed-out role.
     *   3. NavigationRequiredPaths (/manage), when there's a navigation: not allowed by it, so no.
     *      ProtectedPaths (/json, /manage): signed in only. The endpoint's own checks still apply
     *      (VisibleTo(), and admins only on generated /manage pages).
     *   4. A secure master page (SetSecureMasterPages): signed in, and, when there's a navigation,
     *      allowed by it.
     *   5. Anything else (a public master page, or none): anyone.
     *
     * @param bool $after_init false = path rules (1-3), true = master page rule (4)
     * @return void
     */
    public function EnforceAccess(bool $after_init = false): void
    {
        if (!$this->AccessCheckEnabled || PHP_SAPI === 'cli') {
            return;
        }

        switch ($this->AccessDecision($after_init)) {
            case 'signin':
                $this->Deny(false);
                break;
            case 'deny':
                $this->Deny(true);
                break;
        }
    }

    /**
     * The rules EnforceAccess() applies, without acting on them.
     *
     * @param bool $after_init
     * @return string 'allow', 'signin' (not signed in) or 'deny' (signed in, not allowed)
     */
    public function AccessDecision(bool $after_init = false): string
    {
        $page = $this->CurrentPage ?? '/';

        if (self::PathStartsWith($page, $this->PublicPaths)) {
            return 'allow';
        }
        if ($this->AccessNavigation && $this->AccessNavigation->Allows($page)) {
            return 'allow';
        }

        $signed_in = $this->IsSignedIn();

        if (!$after_init) {
            if ($this->AccessNavigation && self::PathStartsWith($page, $this->NavigationRequiredPaths)) {
                return $signed_in ? 'deny' : 'signin';
            }
            if (self::PathStartsWith($page, $this->ProtectedPaths) && !$signed_in) {
                return 'signin';
            }
            return 'allow';
        }

        if (!$this->IsSecureMasterPage()) {
            return 'allow';
        }
        if (!$signed_in) {
            return 'signin';
        }
        return $this->AccessNavigation ? 'deny' : 'allow';
    }

    /**
     * @param bool $signed_in
     * @return void
     */
    private function Deny(bool $signed_in): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? ($this->CurrentPage ?? '/');
        $wants_json = $this->IsJSON
            || self::PathStartsWith($this->CurrentPage ?? '/', ['/json'])
            || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

        if ($wants_json) {
            HTTP::ExitJSON(
                ['error' => $signed_in ? 'You do not have permission to do that' : 'Please sign in'],
                $signed_in ? HTTP::HTTP_STATUS_FORBIDDEN : HTTP::HTTP_STATUS_UNAUTHORIZED
            );
        }

        if (!$signed_in) {
            $signin = $this->SignInPage ?: ($this->DefaultPage ?: '/');
            if ($signin[0] !== '/') {
                $signin = '/' . $signin;
            }
            HTTP::Redirect($signin . (str_contains($signin, '?') ? '&' : '?') . 'continue=' . urlencode($uri));
        }

        HTTP::RedirectError('You do not have permission to view that page', $this->DefaultUserPage ?: '/');
    }

    /**
     * @return void
     */
    public function Exec(): void
    {

        if ($this->IndexFile) {
            header('Content-Type: text/html; charset=UTF-8');
            readfile($this->IndexFile);
            exit();
        }

        if ($this->ControllerFile) {
            $this->EnforceAccess();

            if ($this->RequireCSRFToken) {
                Csrf::Check($this->Verb);
            }

            require_once $this->ControllerFile;

            /* @var BasePage $class */
            $class = $this->PageClass;

            $class::Init();

            $this->MasterPage = $class::getMasterPage();
            $this->EnforceAccess(true);

            switch ($this->Verb) {
                case 'GET':
                    $class::Get();
                    if ($_REQUEST['export'] ?? null) {
                        $this->Export($this->PageClass, $_REQUEST['export']);
                    }
                    break;
                case 'POST':
                    $class::Post();
                    if ($_REQUEST['export'] ?? null) {
                        $this->Export($this->PageClass, $_REQUEST['export']);
                    }
                    break;
                case 'PUT':
                    $class::Put();
                    break;
                case 'PATCH':
                    $class::Patch();
                    break;
                case 'DELETE':
                    $class::Delete();
                    break;
                case 'OPTIONS':
                    $class::Options();
                    break;
                case 'FIND':
                    $class::Find();
                    break;
                case 'HISTORY':
                    if (!method_exists($class, 'History')) {
                        exit('unknown REQUEST_METHOD: ' . $this->Verb);
                    }
                    $class::History();
                    break;
                default:
                    exit('unknown REQUEST_METHOD: ' . $this->Verb);
            }

            if ($this->ViewFile) {
                ob_start();
                require_once $this->ViewFile;
                $this->HTML = ob_get_clean();
            }
            $this->MetaTitle = $class::getMetaTitle();

            if($this->NoCache) {
                header('Cache-Control: no-cache, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
            }

            if ($class::getMasterPage()) {
                if (strtoupper(($_REQUEST['export'] ?? '')) === 'PDF') {
                    ob_start();
                    require_once $this->ScriptDir . '/masterpages/' . $class::getMasterPage();
                    $this->HTML = ob_get_clean();
                    WebKit::Render($this);
                } else {
                    require_once $this->ScriptDir . '/masterpages/' . $class::getMasterPage();
                }
            } else {
                exit ($this->HTML ?? '');
            }

        }
    }

    /**
     * @param string $class
     * @param string $export
     * @return void
     */
    public function Export(
        string $class,
        string $export
    ): void
    {
        /* @var BasePage $class */
        switch(strtoupper($export)) {
            case 'CSV':
                $class::ExportToCSV();
                break;
            case 'XLS':
                $class::ExportToXLS();
                break;
            case 'JSON':
                $class::ExportToJSON();
                break;
            case 'PDF':
                $class::ExportToPDF();

                $this->RenderPDF = true;
                $this->PDFMargins = $class::$PDFMargins;
                $this->PDFPageSize = $class::$PDFPageSize;
                $this->PDFFileName = $class::$PDFFileName;
                $this->PDFPageOrientation = $class::$PDFPageOrientation;
                $this->PDFShrinkToFit = $class::$PDFShrinkToFit;
                break;
        }
    }
}