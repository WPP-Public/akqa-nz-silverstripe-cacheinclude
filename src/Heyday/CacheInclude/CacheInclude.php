<?php

namespace Heyday\CacheInclude;

use CacheCache\Cache;
use Heyday\CacheInclude\KeyCreatorInterface;
use Heyday\CacheInclude\ConfigInterface;
use Heyday\CacheInclude\ProcessorInterface;

class CacheInclude
{

    //DI services
    protected $keyCreator;
    protected $cache;
    protected $config;

    //Configuration props
    protected $enabled = true;
    protected $forceExpire = false;
    protected $defaultConfig = array(
        'context' => 'no',
        'member' => false,
        'expires' => false
    );

    public function __construct(
        Cache $cache,
        KeyCreatorInterface $keyCreator,
        ConfigInterface $config,
        $forceExpire = false
    )
    {
        $this->cache = $cache;
        $this->keyCreator = $keyCreator;
        $this->config = $config;
        $this->forceExpire = $forceExpire;
    }

    public function setEnabled($enabled)
    {
        $this->enabled = (boolean) $enabled;
    }

    public function getEnabled()
    {
        return $this->enabled;
    }

    public function setDefaultConfig($config)
    {
        $this->defaultConfig = $config;
    }

    public function getDefaultConfig()
    {
        return $this->defaultConfig;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function setForceExpire($forceExpire)
    {
        $this->forceExpire = (boolean) $forceExpire;
    }

    public function getForceExpire()
    {
        return $this->forceExpire;
    }

    public function getCombinedConfig($name)
    {
        $config = $this->defaultConfig;
        if (isset($this->config[$name]) && is_array($this->config[$name])) {
            $config = $this->config[$name] + $config;
        }

        return $config;
    }

    public function process($name, $processor, \Controller $controller)
    {
        if (!$processor instanceof ProcessorInterface && !is_callable($processor)) {
            throw new \InvalidArgumentException('The argument $processor must be an instance of ProcessorInterface or a callable');
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

        if ($this->forceExpire || ($result = $this->cache->get($key)) === null) {

            if (isset($config['expires']) && is_string($config['expires'])) {
                $expires = strtotime($config['expires']) - date('U');
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

    protected function resetStoredKeys($name)
    {
        $this->cache->set($name, array());
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
        $this->resetStoredKeys($name);
    }

    public function flushAll()
    {
        $this->cache->flushAll();
        foreach ($this->cache as $name => $config) {
            $this->resetStoredKeys($name);
        }
    }

}
