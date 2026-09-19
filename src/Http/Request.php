<?php
namespace FAAPI\Http;

/**
 * The parts of the incoming request the API uses. Built from values captured
 * before FrontAccounting's session code runs, because that code HTML-escapes
 * $_SERVER, $_GET and $_POST in place.
 */
final class Request
{
    private $method;
    private $path;
    private $query;
    private $form;
    private $headers;

    /**
     * @param array $raw method, uri, script, path_info, query, body, headers
     */
    public function __construct(array $raw)
    {
        $headers = array();
        foreach ((array) (isset($raw['headers']) ? $raw['headers'] : array()) as $name => $value) {
            $headers[strtolower($name)] = $value;
        }
        $this->headers = $headers;

        $method = strtoupper(isset($raw['method']) ? $raw['method'] : 'GET');
        $override = isset($headers['x-http-method-override']) ? $headers['x-http-method-override'] : null;
        $this->method = ($method === 'POST' && $override) ? strtoupper($override) : $method;

        $this->path = $this->resolvePath($raw);

        $this->query = array();
        parse_str(isset($raw['query']) ? (string) $raw['query'] : '', $this->query);

        $this->form = array();
        $body = isset($raw['body']) ? (string) $raw['body'] : '';
        if ($body !== '') {
            $contentType = strtolower(isset($headers['content-type']) ? $headers['content-type'] : '');
            if (strpos($contentType, 'json') !== false) {
                $decoded = json_decode($body, true);
                $this->form = is_array($decoded) ? $decoded : array();
            } else {
                parse_str($body, $this->form);
            }
        }
    }

    public function method()
    {
        return $this->method;
    }

    public function path()
    {
        return $this->path;
    }

    public function get($key = null, $default = null)
    {
        return $this->pick($this->query, $key, $default);
    }

    public function post($key = null, $default = null)
    {
        return $this->pick($this->form, $key, $default);
    }

    public function put($key = null, $default = null)
    {
        return $this->pick($this->form, $key, $default);
    }

    public function headers($name = null, $default = null)
    {
        return $this->pick($this->headers, $name === null ? null : strtolower($name), $default);
    }

    private function pick(array $values, $key, $default)
    {
        if ($key === null) {
            return $values;
        }
        return array_key_exists($key, $values) ? $values[$key] : $default;
    }

    /**
     * The route path relative to this module, e.g. "/customers/".
     */
    private function resolvePath(array $raw)
    {
        $pathInfo = isset($raw['path_info']) ? (string) $raw['path_info'] : '';
        if ($pathInfo !== '') {
            return '/' . ltrim($pathInfo, '/');
        }

        $path = (string) parse_url(isset($raw['uri']) ? (string) $raw['uri'] : '/', PHP_URL_PATH);
        $script = isset($raw['script']) ? (string) $raw['script'] : '';
        $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
        if ($script !== '' && strpos($path, $script) === 0) {
            $path = substr($path, strlen($script));
        } elseif ($base !== '' && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }

        return '/' . ltrim($path, '/');
    }
}
