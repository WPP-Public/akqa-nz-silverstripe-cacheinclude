<?php

namespace Heyday\CacheInclude;

use Stash\Item;
use Stash\Pool;
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
     * @var \Stash\Pool
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
        'member' => false,
        'expires' => false
    );

    /**
     * @param Pool                $cache
     * @param ConfigInterface     $config
     * @param bool                $forceExpire
     */
    public function __construct(
        Pool $cache,
        ConfigInterface $config,
        $forceExpire = false
    )
    {
        $this->cache = $cache;
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
     * @param array $keyParts
     * @return string
     */
    protected function prepareKey(array $keyParts)
    {
        return implode('.', $keyParts);
    }

    /**
     * @param                            $name
     * @param                            $processor
     * @param KeyCreators\KeyCreatorInterface $keyCreator
     * @throws \InvalidArgumentException
     * @return mixed|null
     */
    public function process($name, $processor, KeyCreatorInterface $keyCreator)
    {
        if (!$processor instanceof ProcessorInterface && !is_callable($processor)) {
            throw new \InvalidArgumentException(
                'The argument $processor must be an instance of ProcessorInterface or a callable'
            );
        }

        if (!$this->enabled) {
            return $processor($name);
        }

        $key = $this->prepareKey(
            $keyCreator->getKey(
                $name,
                $config = $this->getCombinedConfig($name)
            )
        );

        $item = $this->cache->getItem($key);
        $result = $item->get();

        if ($this->forceExpire) {
            $item->clear();
            $this->removeStoredKey($name, $key);
            $result = $processor($name);
        } elseif ($item->isMiss()) {
            $result = $processor($name);
            $item->set($result, $this->getExpiry($config));
            $this->addStoredKey($name, $key);
        }

        return $result;
    }

    /**
     * @param                     $name
     * @param KeyCreatorInterface $keyCreator
     * @return mixed
     */
    public function get($name, KeyCreatorInterface $keyCreator)
    {
        $key = $this->prepareKey(
            $keyCreator->getKey(
                $name,
                $this->getCombinedConfig($name)
            )
        );

        return $this->cache->getItem($key)->get();
    }

    /**
     * @param                     $name
     * @param                     $result
     * @param KeyCreatorInterface $keyCreator
     */
    public function set($name, $result, KeyCreatorInterface $keyCreator)
    {
        if (!$this->enabled) {
            return;
        }
        $key = $this->prepareKey(
            $keyCreator->getKey(
                $name,
                $config = $this->getCombinedConfig($name)
            )
        );

        $this->cache->getItem($key)->set($result, $this->getExpiry($config));
        $this->addStoredKey($name, $key);
    }

    /**
     * @param $name
     * @param $key
     */
    protected function addStoredKey($name, $key)
    {
        $item = $this->cache->getItem($name);
        $keys = (array) $item->get();
        if (!array_key_exists($key, $keys)) {
            $keys[$key] = true;
            $item->set($keys);
        }
    }

    /**
     * @param $name
     * @param $key
     */
    protected function removeStoredKey($name, $key)
    {
        $item = $this->cache->getItem($name);
        $keys = (array) $item->get();
        if (array_key_exists($key, $keys)) {
            unset($keys[$key]);
            $item->set($keys);
        }
    }

    /**
     * @param $name
     */
    public function flushByName($name)
    {
        $item = $this->cache->getItem($name);
        $keys = array_keys((array) $this->cache->getItem($name)->get());
        $iterator = $this->cache->getItemIterator($keys);
        foreach ($iterator as $item) {
            $item->clear();
        }
        $item->set(array());
    }

    /**
     *
     */
    public function flushAll()
    {
        $this->cache->purge();
    }

    /**
     * @param $config
     * @return \DateTime|null
     */
    protected function getExpiry($config)
    {
        if (isset($config['expires']) && is_string($config['expires'])) {
            $expires = new \DateTime($config['expires']);
        } else {
            $expires = null;
        }

        return $expires;
    }
}
