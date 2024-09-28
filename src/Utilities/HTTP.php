<?php

namespace Bkucenski\Quickdry\Utilities;

// HTTPStatus


use JetBrains\PhpStorm\NoReturn;
use Bkucenski\Quickdry\JSON\JsonStatusResult;




/**
 * Class HTTP
 */
class HTTP
{
    public const HTTP_STATUS_CONTINUE = 100;
    public const HTTP_STATUS_SWITCHING_PROTOCOLS = 101;
    public const HTTP_STATUS_OK = 200;
    public const HTTP_STATUS_CREATED = 201;
    public const HTTP_STATUS_ACCEPTED = 202;
    public const HTTP_STATUS_NON_AUTHORITATIVE_INFORMATION = 203;
    public const HTTP_STATUS_NO_CONTENT = 204;
    public const HTTP_STATUS_RESET_CONTENT = 205;
    public const HTTP_STATUS_PARTIAL_CONTENT = 206;
    public const HTTP_STATUS_MULTIPLE_CHOICES = 300;
    public const HTTP_STATUS_MOVED_PERMANENTLY = 301;
    public const HTTP_STATUS_FOUND = 302;
    public const HTTP_STATUS_SEE_OTHER = 303;
    public const HTTP_STATUS_NOT_MODIFIED = 304;
    public const HTTP_STATUS_USE_PROXY = 305;
    public const HTTP_STATUS_TEMPORARY_REDIRECT = 307;
    public const HTTP_STATUS_BAD_REQUEST = 400;
    public const HTTP_STATUS_UNAUTHORIZED = 401;
    public const HTTP_STATUS_PAYMENT_REQUIRED = 402;
    public const HTTP_STATUS_FORBIDDEN = 403;
    public const HTTP_STATUS_NOT_FOUND = 404;
    public const HTTP_STATUS_METHOD_NOT_ALLOWED = 405;
    public const HTTP_STATUS_NOT_ACCEPTABLE = 406;
    public const HTTP_STATUS_PROXY_AUTHENTICATION_REQUIRED = 407;
    public const HTTP_STATUS_REQUEST_TIMEOUT = 408;
    public const HTTP_STATUS_CONFLICT = 409;
    public const HTTP_STATUS_GONE = 410;
    public const HTTP_STATUS_LENGTH_REQUIRED = 411;
    public const HTTP_STATUS_PRECONDITION_FAILED = 412;
    public const HTTP_STATUS_PAYLOAD_TOO_LARGE = 413;
    public const HTTP_STATUS_URI_TOO_LONG = 414;
    public const HTTP_STATUS_UNSUPPORTED_MEDIA_TYPE = 415;
    public const HTTP_STATUS_RANGE_NOT_SATISFIABLE = 416;
    public const HTTP_STATUS_EXPECTATION_FAILED = 417;
    public const HTTP_STATUS_UPGRADE_REQUIRED = 426;
    public const HTTP_STATUS_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_STATUS_NOT_IMPLEMENTED = 501;
    public const HTTP_STATUS_BAD_GATEWAY = 502;
    public const HTTP_STATUS_SERVICE_UNAVAILABLE = 503;
    public const HTTP_STATUS_GATEWAY_TIMEOUT = 504;
    public const HTTP_STATUS_HTTP_VERSION_NOT_SUPPORTED = 505;

// extra
    public const HTTP_STATUS_CALM_DOWN = 420;
    public const HTTP_STATUS_UNPROCESSABLE_ENTITY = 422;
    public const HTTP_STATUS_TOO_MANY_REQUESTS = 429;
    
    /**
     * @param $url
     * @return mixed
     */
    public static function CheckURL($url): mixed
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $retcode;
    }

    /**
     * @param $query_str
     * @param $params
     * @return string
     */
    public static function RemoveParameters($query_str, $params): string
    {
        parse_str($query_str, $get);
        foreach ($params as $param) {
            if (!isset($get[$param])) {
                continue;
            }
            unset($get[$param]);
        }
        return http_build_query($get);
    }

    /**
     * @param JsonStatusResult $result
     */
    #[NoReturn] public static function ExitJSONResult(JsonStatusResult $result): void
    {
        $res = $result->toArray();
        $json = json_decode(json_encode($res), true);

        self::ExitJSON($json, $result->status);
    }

    /**
     * @return bool
     */
    public static function IsSecure(): bool
    {
        if (defined('HTTP_HOST_IS_SECURE') && HTTP_HOST_IS_SECURE) { // needed for sites running behind a proxy
            return true;
        }

        if (!isset($_SERVER['HTTPS'])) {
            return false;
        }

        return
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443;
    }

    /**
     * @param $array
     * @param $name
     * @return string
     */
    public static function ArrayToHTTPQuery($array, $name): string
    {
        $res = [];
        foreach ($array as $v) {
            $res[] = $name . '[]=' . urlencode($v);
        }
        return implode('&', $res);
    }


    /**
     * @param string|null $url
     */
    #[NoReturn] public static function Redirect(string $url = null): void
    {
        if (!$url) {
            if (isset($_SERVER['HTTP_REFERER'])) {
                header('location: ' . $_SERVER['HTTP_REFERER']);
            } else {
                header('location: /');
            }
        } else {
            header('location: ' . $url);
        }
        exit();
    }

    /**
     * @param $err
     * @param string $url
     */
    public static function RedirectError($err, string $url = '/'): void
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            Log::Insert($err);
            return;
        }

        $_SESSION['error'] = $err;

        if ($url == '/' && isset($_SERVER['HTTP_REFERER'])) {
            header('location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            header('location: ' . $url);
        }
        exit();
    }

    /**
     * @param $notice
     * @param string $url
     */
    #[NoReturn] public static function RedirectNotice($notice, string $url = '/'): void
    {
        $_SESSION['notice'] = $notice;

        if ($url === '/' && isset($_SERVER['HTTP_REFERER'])) {
            header('location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            header('location: ' . $url);
        }
        exit();
    }

    /**
     *
     */
    #[NoReturn] public static function ReloadPage(): void
    {
        header('location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    /**
     * @param $url
     * @param $title
     */
    #[NoReturn] public static function ExitJavascript($url, $title): void
    {
        echo 'Redirecting to <a id="redirect_url" href="' . $url . '">' . $title . '</a><script>
    (function() {
        window.location = document.getElementById("redirect_url");
    })();
    </script>
    ';
        exit;
    }

    // https://stackoverflow.com/questions/6041741/fastest-way-to-check-if-a-string-is-json-in-php

    /**
     * @param string $string
     * @return bool
     */
    public static function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * @param $json
     * @param int $HTTP_STATUS
     * @return void
     */
    #[NoReturn] public static function ExitOData($json, int $HTTP_STATUS = self::HTTP_STATUS_OK): void
    {
        if ($HTTP_STATUS) {
            header('HTTP/1.1 ' . $HTTP_STATUS . ': ' . HTTPStatus::GetDescription($HTTP_STATUS));
        }
        header('Content-Type: application/json;odata.metadata=minimal;odata.streaming=true;IEEE754Compatible=false;charset=utf-8');
        header('OData-Version: 4.0');
        header('Cache-Control: no-store');
        header('Pragma: no-cache');

        if(is_string($json)) {
            if (self::isJson($json)) {
                exit($json);
            }
        }

        $json = json_encode(Strings::FixJSON($json), JSON_PRETTY_PRINT);
        $error = json_last_error_msg();
        if(json_last_error()) {
            exit($error);
        }
        exit($json);

    }
    /**
     * @param $json
     * @param int $HTTP_STATUS
     */
    #[NoReturn] public static function ExitJSON($json, int $HTTP_STATUS = self::HTTP_STATUS_OK): void
    {
        if($_SERVER['HTTP_HOST'] ?? null) {
            if ($HTTP_STATUS) {
                header('HTTP/1.1 ' . $HTTP_STATUS . ': ' . HTTPStatus::GetDescription($HTTP_STATUS));
            }
            header('Content-Type: application/json');
            header('Cache-Control: no-store');
            header('Pragma: no-cache');
        }

        if(is_string($json)) {
            if (self::isJson($json)) {
                exit($json);
            }
        }

        $json = json_encode(Strings::FixJSON($json), JSON_PRETTY_PRINT);
        $error = json_last_error_msg();
        if(json_last_error()) {
            exit($error);
        }
        exit($json);
    }

    /**
     * @param string $content
     * @param string $filename
     * @param int $HTTP_STATUS
     * @param string|null $ContentType
     * @param bool $Download
     */
    #[NoReturn] public static function ExitFile(
        string $content,
        string $filename,
        int $HTTP_STATUS = self::HTTP_STATUS_OK,
        string $ContentType = null,
        bool $Download = true
    ): void
    {
        if ($HTTP_STATUS) {
            header('HTTP/1.1 ' . $HTTP_STATUS . ': ' . HTTPStatus::GetDescription($HTTP_STATUS));
        }

        if ($Download) {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        } else {
            header('Content-Type: ' . $ContentType);
        }

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET,HEAD,OPTIONS,POST,PUT');
        header(
            'Access-Control-Allow-Headers: X-User-Email, X-User-Token, Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers'
        );
        exit($content);
    }

    /**
     * @param $header
     */
    public static function AltHeader($header): void
    {
        if (!defined('NO_HEADERS'))
            header($header);
    }

    /**
     * @param $serialized
     *
     * @return array
     */
    public static function PostFromSerialized($serialized): array
    {
        $reqs = explode('&', $serialized);
        $post = [];
        foreach ($reqs as $req) {
            $nk = explode('=', $req);
            $nk[0] = urldecode($nk[0]);
            if (str_ends_with($nk[0], '[]')) {
                $nk[0] = substr($nk[0], 0, strlen($nk[0]) - 2);
                $post[$nk[0]][] = urldecode($nk[1]);
            } else
                $post[$nk[0]] = isset($nk[1]) ? urldecode($nk[1]) : '';
        }
        return $post;
    }
}

