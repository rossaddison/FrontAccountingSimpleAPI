<?php
namespace FAAPI\Http;

/**
 * Lazily-built shared services, so the endpoint classes are only created
 * when a route that needs them runs.
 */
final class Container
{
    /** @var array<string, callable> */
    private $factories = array();

    /** @var array<string, object> */
    private $instances = array();

    public function singleton($name, $factory)
    {
        $this->factories[$name] = $factory;
    }

    public function has($name)
    {
        return isset($this->factories[$name]);
    }

    public function get($name)
    {
        if (!isset($this->instances[$name])) {
            if (!isset($this->factories[$name])) {
                throw new \OutOfBoundsException('No service named ' . $name);
            }
            $this->instances[$name] = call_user_func($this->factories[$name]);
        }
        return $this->instances[$name];
    }
}
