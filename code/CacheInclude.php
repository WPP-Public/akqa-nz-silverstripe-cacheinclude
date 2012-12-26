<?php

class CacheInclude
{

    //DI services
    protected $keyCreator;
    protected $cache;
    protected $config;

    //Configuration props
    protected $delayedProcessing = false;
    protected $enabled = true;
    protected $extraMemory = array();
    protected $forceExpire = false;
    protected $defaultConfig = array(
        'context' => 'no',
        'member' => false,
        'expires' => false
    );

    public function __construct(
        \CacheCache\Cache $cache,
        CacheIncludeKeyCreatorInterface $keyCreator,
        CacheIncludeConfigInterface $config,
        $forceExpire = false
    )
    {
        $this->cache = $cache;
        $this->keyCreator = $keyCreator;
        $this->config = $config;
        $this->forceExpire = $forceExpire;
    }

    public function setDelayedProcessing($enabled)
    {
        $this->delayedProcessing = (boolean) $enabled;
    }

    public function setEnabled($enabled)
    {
        $this->enabled = (boolean) $enabled;
    }

    public function setDefaultConfig($config)
    {
        $this->defaultConfig = $defaultConfig;
    }

    public function setExtraMemory($extraMemory)
    {
        $this->extraMemory = $extraMemory;
    }

    public function getConfig()
    {
        return $this->config;
    }

    protected function getCombinedConfig($name)
    {
        $config = $this->defaultConfig;
        if (isset($this->config[$name]) && is_array($this->config[$name])) {
            $config = $this->config[$name] + $config;
        }
        return $config;
    }

    public function process($name, $processor, Controller $controller)
    {        
        if (!$processor instanceof CacheIncludeProcessorInterface && !is_callable($processor)) {
            throw new InvalidArgumentException('The argument $processor must be an instance of CacheIncludeProcessorInterface or a callable');
        }

        if (!$this->enabled) {
            return $processor($name);
        }

        $config = $this->getCombinedConfig($name);

        $key = $this->keyCreator->getKey(
            $name,
            $controller,
            $config
        );

        if (($result = $this->cache->get($key)) === null) {


            if ($this->forceExpire) {
                $expires = 0;
            } elseif (isset($config['expires']) && is_string($config['expires'])) {
                $expires = strtotime($expires) - date('U');
            } else {
                $expires = null;
            }

            $this->cache->set(
                $key,
                $result = $processor($name),
                $expires
            );

            $this->addStoredKey($name, $key);

        }

        return $result;
    }

    protected function addStoredKey($name, $key)
    {
        $keys = $this->getStoredKeys($name);
        if (!is_array($keys)) {
            $keys = array();
        }
        if (!isset($keys[$key])) {
            $keys[$key] = true;
            $this->cache->set($name, $keys);
        }
    }

    protected function getStoredKeys($name)
    {
        return $this->cache->get($name);
    }

    public function flushByName($name)
    {
        foreach ((array) $this->getStoredKeys($name) as $key => $value) {
            $this->cache->delete($key);
        }
    }

    public function flushAll()
    {
        $this->cache->flushAll();
    }

}