<?php
declare(strict_types=1);

namespace QuickDRY\Connectors;

use QuickDRY\Utilities\Log;
use QuickDRY\Web\Session;
use stdClass;

/**
 * @property array|null headers
 * @property mixed res
 * @property array|stdClass|string|null path
 * @property array|stdClass|string|null data
 * @property string error
 */
class APIRequest
{
    private ?string $_path = null;
    private ?string $_method = null;
    private ?stdClass $_res = null;
    private ?string $_error = null;
    private ?array $_data = null;
    private ?string $_raw = null;
    private ?array $_headers = null;
    private ?string $_cache_file = null;
    private ?array $_return_headers = null;

    /* @var mixed $_curl_info */
    private mixed $_curl_info = null;

    public static bool $UseLog = false;
    public static ?int $CacheTimeoutSeconds = null;
    public static ?bool $ShowURL = null;

    /**
     * @param string $name
     * @return null
     */
    public function __get(string $name)
    {
        return match ($name) {
            'method' => $this->_method,
            'curl_info' => $this->_curl_info,
            'return_headers' => $this->_return_headers,
            'headers' => $this->_headers,
            'raw' => $this->_raw,
            'data' => $this->_data,
            'path' => $this->_path,
            'res' => $this->_res,
            'cache_file' => $this->_cache_file,
            'error' => $this->_error,
            default => $this->_res->$name ?? null,
        };
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'curl_info':
                $this->_curl_info = $value;
                break;

            case 'headers':
                $this->_headers = $value;
                break;

            case 'return_headers':
                $this->_return_headers = $value;
                break;

            case 'path':
                $this->_path = $value;
                break;

            case 'res':
                $this->_res = $value;
                break;

            case 'cache_file':
                $this->_cache_file = $value;
                break;

            case 'raw':
                $this->_raw = $value;
                break;

            case 'error':
                $this->_error = $value;
                break;

            default:
                $this->_data[$name] = $value;
        }
    }

    /**
     * @return void
     */
    public function ClearCache(): void
    {
        $file = $this->_cache_file;

        if ($file && file_exists($file) && filesize($file)) {
            rename($file, 'last_attempt.txt');
            //unlink($file);
        }

    }

    /**
     * @param string $path
     * @param array|null $data
     * @param array|null $headers
     * @param bool $post
     * @param string|null $custom_method
     * @return bool|string|null
     */
    private function _Request(
        string  $path,
        ?array  $data = null,
        ?array  $headers = null,
        bool    $post = true,
        ?string $custom_method = null
    ): bool|string|null
    {
        $file = null;

        if (self::$CacheTimeoutSeconds) {
            $hash = md5(serialize([$path, $data, $headers, $post]));
            $dir = DOC_ROOT_PATH . '/logs/cache';
            if (!is_dir($dir)) {
                mkdir($dir);
            }
            $dir .= '/' . $hash[0];
            if (!is_dir($dir)) {
                mkdir($dir);
            }
            $dir .= '/' . $hash[1];
            if (!is_dir($dir)) {
                mkdir($dir);
            }

            $file = $dir . '/' . $hash . '.txt';
            $this->_cache_file = $file;

            if (file_exists($file) && filesize($file)) {
                if (time() - filectime($file) < self::$CacheTimeoutSeconds) {
                    $fp = fopen($file, 'r');
                    $retr = fread($fp, filesize($file));
                    fclose($fp);
                    return $retr;
                }
            }
        }

        $ch = curl_init();

        $host = parse_url($path);
        if (!isset($host['host'])) {
            return null;
        }
        $host = $host['host'];

        $url = $path;

        if (is_null($headers)) {
            $headers = [];
        }
        $headers[] = "Host: $host";
        $headers[] = 'Accept: */*';
        $headers[] = 'Accept-Language: en-us';

        if ($data) {
            if ($post) {
                if (isset($data['raw_post_data'])) {
                    $headers[] = 'Content-length: ' . strlen($data['raw_post_data']);
                } elseif (isset($data['json'])) {
                    $headers[] = 'Content-length: ' . strlen(json_encode($data['json']));
                } else {
                    $headers[] = 'Content-length: ' . strlen(http_build_query($data));
                }

            } else {
                $headers[] = 'Content-length: 0';
            }
        } else {
            $headers[] = 'Content-length: 0';
        }

        $headers[] = 'User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)';

        $this->headers = $headers;

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);

        if ($custom_method) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $custom_method);
        }

        curl_setopt($ch, CURLOPT_POST, $post);
        if ($data) {
            if ($post) {
                if (isset($data['raw_post_data'])) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data['raw_post_data']);
                } elseif (isset($data['json'])) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data['json']));
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                }

            } else {
                $url .= (stristr($url, '?') === false ? '?' : '&') . http_build_query($data);
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);

        if (self::$ShowURL) {
            Log::Insert($url);
        }

        // grab URL and pass it to the browser
        $content = curl_exec($ch);

        $this->_error = curl_error($ch);
        $this->_curl_info = curl_getinfo($ch);


        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($content, 0, $header_size);
        $body = substr($content, $header_size);
        curl_close($ch);


        $head = explode("\n", $header);
        $this->_return_headers = [];
        foreach ($head as $val) {
            $val = explode(': ', $val);
            if (isset($val[1])) {
                $this->_return_headers[$val[0]] = $val[1];
            }
        }


        if (self::$CacheTimeoutSeconds) {

            if (file_exists($file)) {
                unlink($file); // delete the old file so the created time updates
            }

            $fp = fopen($file, 'w');
            fwrite($fp, $body);
            fclose($fp);
        }

        return $body;
    }

    /**
     * @param string $path
     * @param array|null $data
     * @param array|null $headers
     * @return bool|string|null
     */
    protected function _Put(string $path, ?array $data = null, ?array $headers = null): bool|string|null
    {
        return $this->_Request($path, $data, $headers, true, 'PUT');
    }

    /**
     * @param string $path
     * @param array|null $data
     * @param array|null $headers
     * @return bool|string|null
     */
    protected function _Delete(string $path, ?array $data = null, ?array $headers = null): bool|string|null
    {
        return $this->_Request($path, $data, $headers, true, 'DELETE');
    }

    /**
     * @param string $path
     * @param array|null $data
     * @param array|null $headers
     * @return bool|string|null
     */
    protected function _Post(string $path, ?array $data = null, ?array $headers = null): bool|string|null
    {
        return $this->_Request($path, $data, $headers);
    }

    /**
     * @param string $path
     * @param array|null $data
     * @param array|null $headers
     * @return bool|string|null
     */
    protected function _Get(string $path, ?array $data = null, ?array $headers = null): bool|string|null
    {
        return $this->_Request($path, $data, $headers, false);
    }

    /**
     * @param string $Method
     */
    protected function _Log(string $Method = 'Get'): void
    {
        $this->_method = $Method;

        if (!self::$UseLog) {
            return;
        }

        $a = Session::Get('api_log');
        $a[] = $this;
        Session::Set('api_log', $a);
    }

    /**
     * @return array
     */
    public function GetProps(): array
    {
        if (!is_null($this->res)) {
            if (is_object($this->res)) {
                $props = get_object_vars($this->res);
            } else {
                $props = $this->res;
            }
        } else {
            $props = [];
        }

        return $props;
    }

    /**
     * @param array|null $headers
     */
    public function Post(?array $headers = null): void
    {
        $res = $this->_Post($this->path, $this->data, $headers);
        $this->_raw = $res;
        $this->res = json_decode($res);
        if (isset($this->res->error)) {
            $this->error = $this->res->error;
        }

        $this->_Log('Post');
    }

    /**
     * @param array|null $headers
     */
    public function Put(?array $headers = null): void
    {
        $res = $this->_Put($this->path, $this->data, $headers);
        $this->_raw = $res;
        $this->res = json_decode($res);
        if (isset($this->res->error)) {
            $this->error = $this->res->error;
        }

        $this->_Log('Put');
    }

    /**
     * @param array|null $headers
     */
    public function Delete(?array $headers = null): void
    {
        $res = $this->_Delete($this->path, $this->data, $headers);
        $this->_raw = $res;
        $this->res = json_decode($res);
        if (isset($this->res->error)) {
            $this->error = $this->res->error;
        }

        $this->_Log('Delete');
    }

    /**
     * @param array|null $headers
     */
    public function Get(?array $headers = null): void
    {
        $res = $this->_Get($this->path, $this->data, $headers);
        $this->_raw = $res;
        $this->res = json_decode($res);
        if (isset($this->res->error)) {
            $this->error = $this->res->error;
        }

        $this->_Log();
    }
}