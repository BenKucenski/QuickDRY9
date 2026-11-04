<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

// HTTPStatus


use QuickDRY\JSON\JsonStatusResult;


/**
 * Class HTTP
 */
class HTTP extends strongType
{
    public const int HTTP_STATUS_CONTINUE = 100;
    public const int HTTP_STATUS_SWITCHING_PROTOCOLS = 101;
    public const int HTTP_STATUS_OK = 200;
    public const int HTTP_STATUS_CREATED = 201;
    public const int HTTP_STATUS_ACCEPTED = 202;
    public const int HTTP_STATUS_NON_AUTHORITATIVE_INFORMATION = 203;
    public const int HTTP_STATUS_NO_CONTENT = 204;
    public const int HTTP_STATUS_RESET_CONTENT = 205;
    public const int HTTP_STATUS_PARTIAL_CONTENT = 206;
    public const int HTTP_STATUS_MULTIPLE_CHOICES = 300;
    public const int HTTP_STATUS_MOVED_PERMANENTLY = 301;
    public const int HTTP_STATUS_FOUND = 302;
    public const int HTTP_STATUS_SEE_OTHER = 303;
    public const int HTTP_STATUS_NOT_MODIFIED = 304;
    public const int HTTP_STATUS_USE_PROXY = 305;
    public const int HTTP_STATUS_TEMPORARY_REDIRECT = 307;
    public const int HTTP_STATUS_BAD_REQUEST = 400;
    public const int HTTP_STATUS_UNAUTHORIZED = 401;
    public const int HTTP_STATUS_PAYMENT_REQUIRED = 402;
    public const int HTTP_STATUS_FORBIDDEN = 403;
    public const int HTTP_STATUS_NOT_FOUND = 404;
    public const int HTTP_STATUS_METHOD_NOT_ALLOWED = 405;
    public const int HTTP_STATUS_NOT_ACCEPTABLE = 406;
    public const int HTTP_STATUS_PROXY_AUTHENTICATION_REQUIRED = 407;
    public const int HTTP_STATUS_REQUEST_TIMEOUT = 408;
    public const int HTTP_STATUS_CONFLICT = 409;
    public const int HTTP_STATUS_GONE = 410;
    public const int HTTP_STATUS_LENGTH_REQUIRED = 411;
    public const int HTTP_STATUS_PRECONDITION_FAILED = 412;
    public const int HTTP_STATUS_PAYLOAD_TOO_LARGE = 413;
    public const int HTTP_STATUS_URI_TOO_LONG = 414;
    public const int HTTP_STATUS_UNSUPPORTED_MEDIA_TYPE = 415;
    public const int HTTP_STATUS_RANGE_NOT_SATISFIABLE = 416;
    public const int HTTP_STATUS_EXPECTATION_FAILED = 417;
    public const int HTTP_STATUS_UPGRADE_REQUIRED = 426;
    public const int HTTP_STATUS_INTERNAL_SERVER_ERROR = 500;
    public const int HTTP_STATUS_NOT_IMPLEMENTED = 501;
    public const int HTTP_STATUS_BAD_GATEWAY = 502;
    public const int HTTP_STATUS_SERVICE_UNAVAILABLE = 503;
    public const int HTTP_STATUS_GATEWAY_TIMEOUT = 504;
    public const int HTTP_STATUS_HTTP_VERSION_NOT_SUPPORTED = 505;

// extra
    public const int HTTP_STATUS_CALM_DOWN = 420;
    public const int HTTP_STATUS_UNPROCESSABLE_ENTITY = 422;
    public const int HTTP_STATUS_TOO_MANY_REQUESTS = 429;

    public static bool $AJAXTestMode = false;

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
     * @param string $query_str
     * @param array $params
     * @return string
     */
    public static function RemoveParameters(string $query_str, array $params): string
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
     * @param string $query_str
     * @param array $params
     * @return string
     */
    public static function AddParameters(string $query_str, array $params): string
    {
        // Parse existing query string into array
        parse_str($query_str, $get);

        // Merge with new params (overwrites if already exist)
        foreach ($params as $key => $value) {
            $get[$key] = $value;
        }

        // Build updated query string
        return http_build_query($get);
    }


    /**
     * @param JsonStatusResult $result
     */
    public static function ExitJSONResult(JsonStatusResult $result): void
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
    public static function Redirect(?string $url = null): void
    {
        if (self::$AJAXTestMode) {
            return;
        }

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
     * @param string $err
     * @param string|null $url
     */
    public static function RedirectError(string $err, ?string $url = null): void
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            Log::Insert($err);
            return;
        }

        $_SESSION['error'] = $err;

        if (is_null($url) && isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) {
            header('location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            header('location: ' . $url);
        }
        exit();
    }

    /**
     * @param $notice
     * @param string|null $url
     */
    public static function RedirectNotice($notice, ?string $url = null): void
    {
        if (self::$AJAXTestMode) {
            return;
        }

        $_SESSION['notice'] = $notice;

        if (is_null($url) && isset($_SERVER['HTTP_REFERER'])) {
            header('location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            header('location: ' . $url);
        }
        exit();
    }

    /**
     *
     */
    public static function ReloadPage(): void
    {
        if (self::$AJAXTestMode) {
            return;
        }

        header('location: ' . $_SERVER['REQUEST_URI']);
        exit();
    }

    /**
     * @param $url
     * @param $title
     */
    public static function ExitJavascript($url, $title): void
    {
        if (self::$AJAXTestMode) {
            return;
        }

        echo 'Redirecting to <a id="redirect_url" href="' . $url . '">' . $title . '</a><script>
    (function() {
        window.location = document.getElementById("redirect_url");
    })();
    </script>
    ';
        exit();
    }

    // https://stackoverflow.com/questions/6041741/fastest-way-to-check-if-a-string-is-json-in-php

    /**
     * @param string $string
     * @return bool
     */
    public static function isJson(string $string): bool
    {
        return json_validate($string);
    }

    /**
     * @param $json
     * @param int $HTTP_STATUS
     * @return void
     */
    public static function ExitOData($json, int $HTTP_STATUS = self::HTTP_STATUS_OK): void
    {
        if (self::$AJAXTestMode) {
            return;
        }

        if ($HTTP_STATUS) {
            header('HTTP/1.1 ' . $HTTP_STATUS . ': ' . HTTPStatus::GetDescription($HTTP_STATUS));
        }
        header('Content-Type: application/json;odata.metadata=minimal;odata.streaming=true;IEEE754Compatible=false;charset=utf-8');
        header('OData-Version: 4.0');
        header('Cache-Control: no-store');
        header('Pragma: no-cache');

        if (is_string($json)) {
            if (self::isJson($json)) {
                exit($json);
            }
        }

        $json = json_encode($json, JSON_PRETTY_PRINT);
        $error = json_last_error_msg();
        if (json_last_error()) {
            exit($error);
        }
        exit($json);

    }

    /**
     * @param $json
     * @param int $HTTP_STATUS
     * @return string|void
     */
    public static function ExitJSON($json, int $HTTP_STATUS = self::HTTP_STATUS_OK)
    {
        if (self::$AJAXTestMode) {
            return $json;
        }

        if ($_SERVER['HTTP_HOST'] ?? null) {
            if ($HTTP_STATUS) {
                header('HTTP/1.1 ' . $HTTP_STATUS . ': ' . HTTPStatus::GetDescription($HTTP_STATUS));
            }
            header('Content-Type: application/json');
            header('Cache-Control: no-store');
            header('Pragma: no-cache');
        }

        if (is_string($json)) {
            if (self::isJson($json)) {
                if (self::$AJAXTestMode) {
                    return $json;
                }
                exit($json);
            }
        }

        $json = json_encode($json, JSON_PRETTY_PRINT);
        $error = json_last_error_msg();
        if (json_last_error()) {
            if (self::$AJAXTestMode) {
                Testing($error);
            }
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
    public static function ExitFile(
        string  $content,
        string  $filename,
        int     $HTTP_STATUS = self::HTTP_STATUS_OK,
        ?string $ContentType = null,
        bool    $Download = true
    ): void
    {
        if (ob_get_length()) {
            ob_end_clean();
        }

        if (self::$AJAXTestMode) {
            return;
        }

        if ($HTTP_STATUS) {
            header('HTTP/1.1 ' . $HTTP_STATUS . ': ' . HTTPStatus::GetDescription($HTTP_STATUS));
        }

        if ($Download) {
            // Set headers to initiate download
            $contentType = 'application/octet-stream';
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . strlen($content));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Expires: 0');

            // Output the file content
            echo $content;
            exit();
        } else {
            header('Content-Type: ' . $ContentType);
        }

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

