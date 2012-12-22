<?php

class CacheInclude
{

    //DI services
    protected $keyCreator;
    protected $cache;

    //Configuration props
    protected $delayedProcessing = false;
    protected $enabled = true;
    protected $extraMemory = array();
    protected $config = array();
    protected $forceExpire = false;
    protected $defaultConfig = array(
        'context' => 'no',
        'member' => false,
        'expires' => false
    );

    public function __construct(
        \CacheCache\Cache $cache,
        CacheIncludeKeyCreatorInterface $keyCreator,
        $config,
        $forceExpire = false
    )
    {
        $this->cache = $cache;
        $this->keyCreator = $keyCreator;
        $this->setConfig($config);
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

    public function setConfig($config)
    {
        $this->config = array_merge($this->config, $config);
    }

    public function setDefaultConfig($config)
    {
        $this->defaultConfig = $defaultConfig;
    }

    public function setExtraMemory($extraMemory)
    {
        $this->extraMemory = $extraMemory;
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

        $key = implode(
            '_',
            (array) $this->keyCreator->getKeyParts(
                $controller,
                $config
            )
        );

        if (($result = $this->cache->get($key)) === null || $this->forceExpire) {

            // $this->ensureExtraMemory();

            $this->cache->set(
                $key,
                $result = $processor($name),
                isset($config['expires']) ? strtotime($expires) - date('U') : null
            );

        }

        return $result;
    }

}