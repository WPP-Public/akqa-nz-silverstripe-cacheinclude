<?php

class CacheIncludeArrayConfig implements CacheIncludeConfigInterface
{
    protected $config;
    
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function offsetSet($id, $value)
    {
        $this->config[$id] = $value;
    }

    public function offsetGet($id)
    {
        if (!array_key_exists($id, $this->config)) {
            throw new InvalidArgumentException(sprintf('Config "%s" is not defined.', $id));
        }
        return $this->config[$id];
    }
    
    public function offsetExists($id)
    {
        return isset($this->config[$id]);
    }

    public  function offsetUnset($id)
    {
        unset($this->config[$id]);
    }
}