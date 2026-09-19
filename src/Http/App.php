<?php
namespace FAAPI\Http;

/**
 * A small router for this module, replacing Slim 2.x, which is unmaintained
 * and no longer compatible with PHP 8.1+ (its ArrayAccess/Iterator classes
 * lack the required return types).
 *
 * It implements only the slice of Slim 2's API this module used: route
 * registration with :name segments (get/post/put/delete/group), shared
 * services, a "slim.before" hook (used for login), request/response objects
 * and halt(). Routes match with or without a trailing slash.
 */
final class App
{
    /** @var array<string, self> */
    private static $instances = array();

    private $name = 'default';
    private $settings;
    private $routes = array();
    private $prefix = '';
    private $beforeHooks = array();
    private $request;
    private $response;

    /** @var Container */
    public $container;

    public function __construct(array $settings = array(), array $raw = array())
    {
        $this->settings = $settings;
        $this->container = new Container();
        $this->request = new Request($raw);
        $this->response = new Response();
    }

    public static function getInstance($name = 'default')
    {
        if (!isset(self::$instances[$name])) {
            throw new \OutOfBoundsException('No application named ' . $name);
        }
        return self::$instances[$name];
    }

    public function setName($name)
    {
        $this->name = $name;
        self::$instances[$name] = $this;
    }

    public function __get($name)
    {
        return $this->container->get($name);
    }

    public function request()
    {
        return $this->request;
    }

    public function response()
    {
        return $this->response;
    }

    public function hook($name, $callable)
    {
        if ($name === 'slim.before') {
            $this->beforeHooks[] = $callable;
        }
    }

    public function group($prefix, $callable)
    {
        $previous = $this->prefix;
        $this->prefix .= $prefix;
        call_user_func($callable);
        $this->prefix = $previous;
    }

    public function get($pattern, $callable)
    {
        $this->route('GET', $pattern, $callable);
    }

    public function post($pattern, $callable)
    {
        $this->route('POST', $pattern, $callable);
    }

    public function put($pattern, $callable)
    {
        $this->route('PUT', $pattern, $callable);
    }

    public function delete($pattern, $callable)
    {
        $this->route('DELETE', $pattern, $callable);
    }

    public function halt($code, $body = '')
    {
        $this->response->status($code);
        $this->response->body($body);
        throw new Halt();
    }

    public function run()
    {
        set_error_handler(function ($number, $message, $file, $line) {
            if (!(error_reporting() & $number)) {
                return false;
            }
            throw new \ErrorException($message, 0, $number, $file, $line);
        });

        try {
            foreach ($this->beforeHooks as $hook) {
                call_user_func($hook);
            }
            $this->dispatch();
        } catch (Halt $halt) {
            // the response was already set by halt()
        } catch (\Throwable $e) {
            $this->fail($e);
        }
        restore_error_handler();

        $this->response->send();
    }

    private function route($method, $pattern, $callable)
    {
        $path = rtrim($this->prefix . $pattern, '/');
        $regex = '';
        foreach (preg_split('#(:[A-Za-z_][A-Za-z0-9_]*)#', $path, -1, PREG_SPLIT_DELIM_CAPTURE) as $part) {
            $regex .= (isset($part[0]) && $part[0] === ':') ? '([^/]+)' : preg_quote($part, '#');
        }
        $this->routes[] = array('method' => $method, 'regex' => '#^' . $regex . '/?$#', 'handler' => $callable);
    }

    private function dispatch()
    {
        $path = $this->request->path();
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $this->request->method()) {
                continue;
            }
            array_shift($matches);
            call_user_func_array($route['handler'], array_map('urldecode', $matches));
            return;
        }

        $code = $pathMatched ? 405 : 404;
        $this->halt($code, json_encode(array(
            'code' => $code,
            'success' => 0,
            'msg' => $pathMatched ? 'Method Not Allowed' : 'Not Found',
        )));
    }

    private function fail(\Throwable $e)
    {
        $body = array(
            'code' => 500,
            'success' => 0,
            'msg' => get_class($e) . ': ' . $e->getMessage(),
        );
        if (!empty($this->settings['debug'])) {
            $body['file'] = $e->getFile();
            $body['line'] = $e->getLine();
        }
        $this->response->status(500);
        $this->response->body(json_encode($body));
    }
}
