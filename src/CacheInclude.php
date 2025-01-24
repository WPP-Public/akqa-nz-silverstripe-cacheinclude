<?php

namespace Heyday\CacheInclude;

use Exception;
use Heyday\CacheInclude\Configs\ConfigInterface;
use Heyday\CacheInclude\KeyCreators\KeyCreatorInterface;
use Heyday\CacheInclude\KeyCreators\KeyInformationProviderInterface;
use Heyday\CacheInclude\Processors\ProcessorInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;

class CacheInclude
{
    /**
     * @var CacheInterface
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
    protected $defaultConfig = [
        'context' => 'no',
        'member' => false,
        'expires' => false
    ];

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param ConfigInterface $config
     * @param bool $forceExpire
     */
    public function __construct(ConfigInterface $config = null, $forceExpire = false)
    {
        $this->cache = Injector::inst()->get(CacheInterface::class . '.CacheInclude');

        if ($config) {
            $this->config = $config;
        }

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
     * @param boolean $forceExpire
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
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Get the default config combined with the provided config
     *
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
     * Prepare a key from key parts
     * @param  array  $keyParts
     * @return string
     */
    protected function prepareKey(array $keyParts)
    {
        return implode('.', $keyParts);
    }

    /**
     * @param string $name
     * @param string $processor
     * @param \Heyday\CacheInclude\KeyCreators\KeyCreatorInterface $keyCreator
     * @throws \InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @return mixed
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

        $config = $this->getCombinedConfig($name);
        $key = $this->getKey($name, $keyCreator, $config);

        if ($this->forceExpire) {
            $this->lockCacheAndRun(function () use ($key, $name) {
                $this->cache->delete($key);
                $this->removeStoredKey($name, $key);
            });

            $result = $processor($name);
            $type = 'EXPIRE';
        } elseif (method_exists($this->cache, 'has') && $this->cache->has($key)) {
            $result = $this->cache->get($key);
            $type = 'HIT';
        } elseif (method_exists($this->cache, 'hasItem') && $this->cache->hasItem($key)) {
            $result = $this->cache->get($key);
            $type = 'HIT';
        } else {
            $result = $processor($name);
            $this->runIfCacheLockIsFree(function () use ($key, $name, $result, $keyCreator, $config) {
                if (method_exists($this->cache, 'set')) {
                    $this->cache->set($key, $result, $this->getExpiry($config));
                } else if (method_exists($this->cache, 'save')) {
                    $this->cache->save($key, $result, $this->getExpiry($config));
                } else {
                    throw new RuntimeException('Cache object does not implement set or save method');
                }

                $this->addStoredKey($name, $key, $keyCreator);
            });
            $type = 'MISS';
        }

        $this->log($type, $name, $key);

        return $result;
    }

    /**
     * Run $callback only if the cache hasn't already been locked. As such,
     * $callback is never *guaranteed* to be run. This is used to skip
     * non-essential actions (like writing data to the cache) if we're unable
     * to obtain a lock
     *
     * @param callable $callback
     * @return mixed
     */
    protected function runIfCacheLockIsFree(callable $callback)
    {
        $fp = fopen($this->getLockFilePath(), 'w+');

        // Attempt a non-blocking lock on the file, bail out if we can't get one
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            return null;
        }

        try {
            $result = $callback();
        } catch (Exception $e) {
            $result = null;
        }

        // Release the lock
        flock($fp, LOCK_UN);
        fclose($fp);

        return $result;
    }

    /**
     * Lock the cache while $callback is running. This is used to prevent race
     * conditions from concurrent requests, and will wait for the lock to be
     * released by any other processes before proceeding.
     *
     * @param callable $callback
     * @return mixed
     */
    protected function lockCacheAndRun(callable $callback)
    {
        $fp = fopen($this->getLockFilePath(), 'w+');

        // Exclusive lock - PHP will wait until the lock is free before continuing
        flock($fp, LOCK_EX);

        try {
            $result = $callback();
        } catch (Exception $e) {
            $result = null;
        }

        // Release the lock
        flock($fp, LOCK_UN);
        fclose($fp);

        return $result;
    }

    /**
     * @return string
     */
    protected function getLockFilePath()
    {
        if ($lockFilePath = Environment::getEnv('SS_CACHEINCLUDE_LOCKFILE_PATH')) {
            return $lockFilePath;
        }

        return TEMP_FOLDER . '/cacheinclude.lock';
    }

    /**
     * @param string $name
     * @param \Heyday\CacheInclude\KeyCreators\KeyCreatorInterface $keyCreator
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get($name, KeyCreatorInterface $keyCreator)
    {
        $key = $this->getKey($name, $keyCreator);
        $result = $this->cache->get($key);

        if ($result) {
            $this->log('HIT', $name, $key);
        } else {
            $this->log('MISS', $name, $key);
        }

        return $result;
    }

    /**
     * @param string $name
     * @param string $result
     * @param \Heyday\CacheInclude\KeyCreators\KeyCreatorInterface $keyCreator
     * @return void
     */
    public function set($name, $result, KeyCreatorInterface $keyCreator)
    {
        if (!$this->enabled) {
            return;
        }

        $config = $this->getCombinedConfig($name);
        $key = $this->getKey($name, $keyCreator, $config);
        $this->runIfCacheLockIsFree(function () use ($name, $result, $keyCreator, $key, $config) {
            $this->cache->set($key, $result, $this->getExpiry($config));
            $this->addStoredKey($name, $key, $keyCreator);
        });
    }

    /**
     * @param string $name
     * @param string $key
     * @param \Heyday\CacheInclude\KeyCreators\KeyCreatorInterface $keyCreator
     * @return void
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function addStoredKey($name, $key, KeyCreatorInterface $keyCreator)
    {
        $keys = (array) $this->cache->get($name);
        if (!array_key_exists($key, $keys)) {
            if ($keyCreator instanceof KeyInformationProviderInterface) {
                $keys[$key] = $keyCreator->getKeyInformation();
            } else {
                $keys[$key] = true;
            }
            $this->cache->set($name, $keys);
        }
    }

    /**
     * @param string $name
     * @param string $key
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function removeStoredKey($name, $key)
    {
        $keys = (array) $this->cache->get($name);
        if (array_key_exists($key, $keys)) {
            unset($keys[$key]);
            $this->cache->set($name, $keys);
        }
    }

    /**
     * Flush the named cache block
     *
     * @param $name
     */
    public function flushByName($name)
    {
        $this->lockCacheAndRun(function() use ($name) {
            $keys = (array) $this->cache->get($name);
            foreach (array_keys($keys) as $key) {
                $this->cache->delete($key);
            }
            $this->cache->set($name, []);
        });
    }

    /**
     * Flush all data from the cache
     */
    public function flushAll()
    {
        $this->lockCacheAndRun(function() {
            $this->cache->clear();
        });
    }

    /**
     * @param $config
     * @return null|int
     */
    protected function getExpiry($config)
    {
        if (isset($config['expires']) && is_string($config['expires'])) {
            $expires = strtotime($config['expires']) - time();
        } else {
            $expires = null;
        }

        return $expires;
    }

    /**
     * @param $name
     * @param KeyCreatorInterface $keyCreator
     * @param array $config
     * @return string
     */
    protected function getKey($name, KeyCreatorInterface $keyCreator, array $config = null)
    {
        $config = $config ?: $this->getCombinedConfig($name);
        $key = $keyCreator->getKey($name, $config);
        return $this->prepareKey($key);
    }

    /**
     * Log an event
     *
     * @param $type
     * @param $name
     * @param $key
     */
    protected function log($type, $name, $key)
    {
        if ($this->logger) {
            $this->logger->info(sprintf("[%s] cacheinclude '%s' with key '%s'", $type, $name, $key));
        }
    }
}
