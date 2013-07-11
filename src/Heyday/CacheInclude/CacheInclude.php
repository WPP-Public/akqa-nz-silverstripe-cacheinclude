<?php

namespace Heyday\CacheInclude;

use CacheCache\Cache;
use Controller;
use Heyday\CacheInclude\Configs\ConfigInterface;
use Heyday\CacheInclude\KeyCreators\KeyCreatorInterface;
use Heyday\CacheInclude\Processors\ProcessorInterface;
use RuntimeException;

/**
 * Class CacheInclude
 * @package Heyday\CacheInclude
 */
class CacheInclude
{
    /**
     * @var KeyCreatorInterface
     */
    protected $keyCreator;
    /**
     * @var \CacheCache\Cache
     */
    protected $cache;
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var bool
     */
    protected $enabled = true;
    /**
     * @var bool
     */
    protected $forceExpire = false;
    /**
     * @var array
     */
    protected $defaultConfig = array(
        'context' => 'no',
        'member'  => false,
        'expires' => false
    );

    /**
     * @param Cache               $cache
     * @param KeyCreatorInterface $keyCreator
     * @param ConfigInterface     $config
     * @param bool                $forceExpire
     */
    public function __construct(
        Cache $cache,
        KeyCreatorInterface $keyCreator,
        ConfigInterface $config,
        $forceExpire = false
    ) {
        $this->cache = $cache;
        $this->keyCreator = $keyCreator;
        $this->config = $config;
        $this->forceExpire = $forceExpire;
    }

    /**
     * @param $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (boolean) $enabled;
    }

    /**
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param $config
     */
    public function setDefaultConfig($config)
    {
        $this->defaultConfig = $config;
    }

    /**
     * @return array
     */
    public function getDefaultConfig()
    {
        return $this->defaultConfig;
    }

    /**
     * @return ConfigInterface
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param $forceExpire
     */
    public function setForceExpire($forceExpire)
    {
        $this->forceExpire = (boolean) $forceExpire;
    }

    /**
     * @return bool
     */
    public function getForceExpire()
    {
        return $this->forceExpire;
    }

    /**
     * @param $name
     * @return mixed
     * @throws \RuntimeException
     */
    public function getCombinedConfig($name)
    {
        $config = $this->defaultConfig;
        if (isset($this->config[$name]) && is_array($this->config[$name])) {
            $config = $this->config[$name] + $config;
        } else {
            throw new RuntimeException("Config '$name' doesn't exist, please check your config");
        }

        return $config;
    }

    /**
     * @param                            $name
     * @param                            $processor
     * @param  \Controller               $controller
     * @return mixed|null
     * @throws \InvalidArgumentException
     */
    public function process($name, $processor, Controller $controller)
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

            $result = $processor($name);

            if ($this->forceExpire) {

                $this->cache->delete($key);
                $this->removeStoredKey($name, $key);

            } else {

                $this->cache->set(
                    $key,
                    $result,
                    $expires
                );

                $this->addStoredKey($name, $key);

            }

        }

        return $result;
    }

    /**
     * @param $name
     * @param $key
     */
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

    /**
     * @param $name
     * @param $key
     */
    protected function removeStoredKey($name, $key)
    {
        $keys = $this->getStoredKeys($name);
        if (!is_array($keys)) {
            $keys = array();
        }
        if (!isset($keys[$key])) {
            unset($keys[$key]);
            $this->cache->set($name, $keys);
        }
    }

    /**
     * @param $name
     */
    protected function resetStoredKeys($name)
    {
        $this->cache->set($name, array());
    }

    /**
     * @param $name
     * @return mixed|null
     */
    protected function getStoredKeys($name)
    {
        return $this->cache->get($name);
    }

    /**
     * @param $name
     */
    public function flushByName($name)
    {
        foreach ((array) $this->getStoredKeys($name) as $key => $value) {
            $this->cache->delete($key);
        }
        $this->resetStoredKeys($name);
    }

    /**
     *
     */
    public function flushAll()
    {
        $this->cache->flushAll();
        foreach ($this->cache as $name => $config) {
            $this->resetStoredKeys($name);
        }
    }
}
