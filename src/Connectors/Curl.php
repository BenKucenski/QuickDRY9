<?php

namespace Bkucenski\Quickdry\Connectors;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
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
    public ?string $Body = null;
    public ?array $HeaderHash = null;
    public ?string $HeaderRaw = null;
    public ?CurlHeader $Header = null;
    public ?int $StatusCode = null;

    public ?string $URL = null;
    public ?array $Params = null;
    public ?array $SentHeader = null;

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
        array             $params = null,
        array             $additional_headers = null
    ): Curl
    {
        $response_header = $response->getHeaders();
        $head_hash = [];
        foreach ($response_header as $name => $values) {
            $head_hash[$name] = implode(';', $values);
        }
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
        array  $params = null,
        array  $additional_headers = null
    ): Curl
    {
        try {
            $start = microtime(true);

            $client = new Client();
            $response = $client->post($path, [
                'query' => $params,
                'headers' => $additional_headers,
                'http_errors' => false,
            ]);

            _dw_api_log::Insert($path, $params, microtime(true) - $start, 'Post');

            return self::getResFromGuzzle($response, $path, $params, $additional_headers);
        } catch (GuzzleException $e) {
            Debug($e);
            return new self();
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
        array  $params = null,
        array  $additional_headers = null
    ): Curl
    {

        try {
            $start = microtime(true);
            $client = new Client();
            $response = $client->post($path, [
                RequestOptions::JSON => $params,
                'headers' => $additional_headers,
                'http_errors' => false,
            ]);

            _dw_api_log::Insert($path, $params, microtime(true) - $start, 'PostJSON');

            return self::getResFromGuzzle($response, $path, $params, $additional_headers);
        } catch (GuzzleException $e) {
            Debug($e);
            return new self();
        }
    }

    /**
     * @param string $path
     * @param string|null $xml
     * @param array|null $additional_headers
     * @return Curl
     */
    public static function PostXML(
        string $path,
        string  $xml = null,
        array  $additional_headers = null
    ): Curl
    {
        $additional_headers['Content-Type'] = 'text/xml; charset=UTF8';

        try {
            $start = microtime(true);
            $client = new Client();
            $response = $client->post($path, [
                'headers' => $additional_headers,
                'body' => $xml,
                'http_errors' => false,
            ]);

            _dw_api_log::Insert($path, ['xml' => $xml], microtime(true) - $start, 'PostXML');

            return self::getResFromGuzzle($response, $path, ['xml' => $xml], $additional_headers);
        } catch (GuzzleException $e) {
            Debug($e);
            return new self();
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
        array  $params = null,
        array  $additional_headers = null
    ): Curl
    {
        try {
            $client = new Client();
            $response = $client->post($path, [
                RequestOptions::FORM_PARAMS => $params,
                'headers' => $additional_headers,
                'http_errors' => false,
            ]);

            return self::getResFromGuzzle($response, $path, $params, $additional_headers);
        } catch (GuzzleException $e) {
            Debug($e);
            return new self();
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
        array  $params = null,
        array  $additional_headers = null
    ): Curl
    {

        try {
            $start = microtime(true);

            $client = new Client();
            $response = $client->get($path, [
                'query' => $params,
                'headers' => $additional_headers,
                'http_errors' => false,
            ]);
            _dw_api_log::Insert($path, $params, microtime(true) - $start, 'Get');

            return self::getResFromGuzzle($response, $path, $params, $additional_headers);
        } catch (GuzzleException $e) {
            Debug($e);
            return new self();
        }
    }

}



