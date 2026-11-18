<?php
declare(strict_types=1);

namespace QuickDRY\Connectors;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use models\CurlLog;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Curl
 * @property CurlHeader Header
 * @property string Body
 * @property string[] HeaderHash
 * @property string HeaderRaw
 * @property int StatusCode
 */
class Curl
{
    private static array $options = [
        'verify' => false,
    ];

    /**
     * Seconds a whole request may take (connect + send + receive) before it
     * is abandoned. Null means no limit, which is Guzzle's default.
     * Set it once, e.g. in env.php or at the top of a task script:
     *   Curl::$timeout = 30;
     */
    public static ?float $timeout = null;

    /**
     * Seconds to wait for the connection to open. Null means no limit.
     */
    public static ?float $connect_timeout = null;

    /**
     * What happens when a request fails outright (timeout, DNS, refused
     * connection). An HTTP error status is not a failure: it comes back in
     * StatusCode as before.
     *   false (default): log it with Exception(), which ends the script.
     *   true: throw the GuzzleException, so the caller can catch it,
     *         retry or skip, and keep going.
     */
    public static bool $throw_exceptions = false;

    public ?string $Body = null;
    public ?array $HeaderHash = null;
    public ?string $HeaderRaw = null;
    public ?CurlHeader $Header = null;
    public ?int $StatusCode = null;

    public ?string $URL = null;
    public ?array $Params = null;
    public ?array $SentHeader = null;

    /**
     * @return Client
     */
    private static function Client(): Client
    {
        $options = self::$options;
        if (!is_null(self::$timeout)) {
            $options[RequestOptions::TIMEOUT] = self::$timeout;
        }
        if (!is_null(self::$connect_timeout)) {
            $options[RequestOptions::CONNECT_TIMEOUT] = self::$connect_timeout;
        }
        return new Client($options);
    }

    /**
     * @param GuzzleException $e
     * @return never
     * @throws GuzzleException
     */
    private static function Failed(GuzzleException $e): never
    {
        if (self::$throw_exceptions) {
            throw $e;
        }
        Exception($e->getMessage());
        exit(1); // Exception() exits; this keeps the never return type honest
    }

    /**
     * @param ResponseInterface $response
     * @param string $path
     * @param array|null $params
     * @param array|null $additional_headers
     * @return Curl
     */
    private static function getResFromGuzzle(
        ResponseInterface $response,
        string            $path,
        ?array            $params = null,
        ?array            $additional_headers = null
    ): Curl
    {
        $response_header = $response->getHeaders();
        $head_hash = array_map(function ($values) {
            return implode(';', $values);
        }, $response_header);
        $status = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        $res = new Curl();
        $res->Body = $body;
        $res->HeaderRaw = json_encode($response_header);
        $res->Header = new CurlHeader($head_hash);
        $res->HeaderHash = $head_hash;
        $res->StatusCode = $status;

        $res->URL = $path;
        $res->Params = $params;
        $res->SentHeader = $additional_headers;

        return $res;
    }

    /**
     * @param string $path
     * @param array|null $params
     * @param array|null $additional_headers
     * @return Curl
     */
    public static function Post(
        string $path,
        ?array $params = null,
        ?array $additional_headers = null
    ): Curl
    {
        try {
            $start = microtime(true);

            $client = self::Client();
            $response = $client->post($path, [
                RequestOptions::FORM_PARAMS => $params,
                'headers'                   => $additional_headers,
                'http_errors'               => false,
            ]);

            CurlLog::Log($path, $params, microtime(true) - $start, 'Post');

            return self::getResFromGuzzle($response, $path, $params, $additional_headers);
        } catch (GuzzleException $e) {
            self::Failed($e);
        }
    }

    /**
     * @param string $path
     * @param array|null $params
     * @param array|null $additional_headers
     * @return Curl
     */
    public static function PostJSON(
        string $path,
        ?array $params = null,
        ?array $additional_headers = null
    ): Curl
    {

        try {
            $start = microtime(true);
            $client = self::Client();
            $response = $client->post($path, [
                RequestOptions::JSON => $params,
                'headers'            => $additional_headers,
                'http_errors'        => false,
            ]);

            CurlLog::Log($path, $params, microtime(true) - $start, 'PostJSON');

            return self::getResFromGuzzle($response, $path, $params, $additional_headers);
        } catch (GuzzleException $e) {
            self::Failed($e);
        }
    }

    /**
     * @param string $path
     * @param array|null $params
     * @param array|null $additional_headers
     * @return Curl
     */
    public static function PatchJSON(
        string $path,
        ?array $params = null,
        ?array $additional_headers = null
    ): Curl
    {

        try {
            $start = microtime(true);
            $client = self::Client();
            $response = $client->patch($path, [
                RequestOptions::JSON => $params,
                'headers'            => $additional_headers,
                'http_errors'        => false,
            ]);

            CurlLog::Log($path, $params, microtime(true) - $start, 'PatchJSON');

            return self::getResFromGuzzle($response, $path, $params, $additional_headers);
        } catch (GuzzleException $e) {
            self::Failed($e);
        }
    }

    /**
     * @param string $path
     * @param string|null $xml
     * @param array|null $additional_headers
     * @return Curl
     */
    public static function PostXML(
        string  $path,
        ?string $xml = null,
        ?array  $additional_headers = null
    ): Curl
    {
        $additional_headers['Content-Type'] = 'text/xml; charset=UTF8';

        try {
            $start = microtime(true);
            $client = self::Client();
            $response = $client->post($path, [
                'headers'     => $additional_headers,
                'body'        => $xml,
                'http_errors' => false,
            ]);

            CurlLog::Log($path, ['xml' => $xml], microtime(true) - $start, 'PostXML');

            return self::getResFromGuzzle($response, $path, ['xml' => $xml], $additional_headers);
        } catch (GuzzleException $e) {
            self::Failed($e);
        }
    }

    /**
     * @param string $path
     * @param array|null $params
     * @param array|null $additional_headers
     * @return Curl
     */
    public static function PostForm(
        string $path,
        ?array $params = null,
        ?array $additional_headers = null
    ): Curl
    {
        try {
            $client = self::Client();
            $response = $client->post($path, [
                RequestOptions::FORM_PARAMS => $params,
                'headers'                   => $additional_headers,
                'http_errors'               => false,
            ]);

            return self::getResFromGuzzle($response, $path, $params, $additional_headers);
        } catch (GuzzleException $e) {
            self::Failed($e);
        }
    }

    /**
     * @param string $path
     * @param array|null $params
     * @param array|null $additional_headers
     * @return Curl
     */
    public static function Get(
        string $path,
        ?array $params = null,
        ?array $additional_headers = null
    ): Curl
    {

        try {
            $start = microtime(true);

            $client = self::Client();
            $response = $client->get($path, [
                'query'       => $params,
                'headers'     => $additional_headers,
                'http_errors' => false,
            ]);
            CurlLog::Log($path, $params, microtime(true) - $start, 'Get');

            return self::getResFromGuzzle($response, $path, $params, $additional_headers);
        } catch (GuzzleException $e) {
            self::Failed($e);
        }
    }

    /**
     * @param string $path
     * @param array|null $params
     * @param array|null $additional_headers
     * @return Curl|void
     */
    public static function PutJSON(
        string $path,
        ?array $params = null,
        ?array $additional_headers = null
    )
    {
        try {
            $start = microtime(true);
            $client = self::Client();
            $response = $client->put($path, [
                RequestOptions::JSON => $params,
                'headers'            => $additional_headers,
                'http_errors'        => false,
            ]);

            CurlLog::Log($path, $params, microtime(true) - $start, 'PutJSON');

            return self::getResFromGuzzle($response, $path, $params, $additional_headers);
        } catch (GuzzleException $e) {
            self::Failed($e);
        }
    }

}



