<?php

namespace Heyday\CacheInclude\Configs;

use Heyday\CacheInclude\ConfigInterface;

class ArrayConfig implements ConfigInterface
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function offsetSet($id, $value)
    {
        throw new \Exception('Configs are immutable');
    }

    public function offsetGet($id)
    {
        if (!array_key_exists($id, $this->config)) {
            throw new \InvalidArgumentException(sprintf('Config "%s" is not defined.', $id));
        }

        return $this->config[$id];
    }

    public function offsetExists($id)
    {
        return isset($this->config[$id]);
    }

    public function offsetUnset($id)
    {
        throw new \Exception('Configs are immutable');
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->config);
    }

}
