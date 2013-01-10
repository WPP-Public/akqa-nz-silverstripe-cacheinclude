<?php

namespace Heyday\CacheInclude;

class Container extends \Pimple
{
    /**
     * Holds an instance of the container
     * @var \Heyday\CacheInclude\Container
     */
    protected static $instance;
    /**
     * Default config of properties
     * @var array
     */
    protected static $config = array(
        //CacheCache
        'cachecache.class'                        => '\CacheCache\Cache',
        'cachecache.options.namespace'            => 'cacheinclude',
        'cachecache.options.default_ttl'          => null,
        'cachecache.options.ttl_variation'        => 0,

        //CacheCache backend
        'cachecache_backend.class'                => '\CacheCache\Backends\File',

        //CacheInclude
        'cacheinclude.class'                      => '\Heyday\CacheInclude\CacheInclude',
        'cacheinclude.options.enabled'            => true,
        'cacheinclude.options.force_expire'       => false,

        //ArrayConfig
        'cacheinclude_config.class'               => '\Heyday\CacheInclude\Configs\ArrayConfig',
        'cacheinclude_config.config'              => array(),

        //KeyCreator
        'cacheinclude_key_creator.class'          => '\Heyday\CacheInclude\KeyCreators\KeyCreator',

        //Processor
        'cacheinclude_processor.class'            => '\Heyday\CacheInclude\Processors\Processor'

    );
    /**
     * Holds user configured extensions of services
     * @var array
     */
    protected static $extensions = array();
    /**
     * Holds user configured shared services
     * @var array
     */
    protected static $shared = array();
    /**
     * Constructs the container and set up default services and properties
     */
    public function __construct()
    {

        //CacheCache
        $this['cachecache'] = $this->share(
            function ($c) {
                return new $c['cachecache.class'](
                    $c['cachecache_backend'],
                    $c['cachecache.options.namespace'],
                    $c['cachecache.options.default_ttl'],
                    $c['cachecache.options.ttl_variation']
                );
            }
        );

        //CacheCache backend
        $this['cachecache_backend'] = $this->share(
            function ($c) {
                return new $c['cachecache_backend.class']($c['cachecache_backend.options']);
            }
        );

        $this['cachecache_backend.options'] = array(
            'dir'            => __DIR__ . '/../../../cache',
            'file_extension' => '.cache'
        );

        //CacheInclude
        $this['cacheinclude'] = $this->share(
            function ($c) {
                $cacheinclude = new $c['cacheinclude.class'](
                    $c['cachecache'],
                    $c['cacheinclude_key_creator'],
                    $c['cacheinclude_config'],
                    $c['cacheinclude.options.force_expire']
                );
                if ($c->offsetExists('cacheinclude.options.enabled')) {
                    $cacheinclude->setEnabled($c['cacheinclude.options.enabled']);
                }
                if ($c->offsetExists('cacheinclude.options.default_config')) {
                    $cacheinclude->setDefaultConfig($c['cacheinclude.options.default_config']);
                }

                return $cacheinclude;
            }
        );

        //CacheIncludeKeyCreator
        $this['cacheinclude_key_creator'] = $this->share(
            function ($c) {
                return new $c['cacheinclude_key_creator.class'];
            }
        );

        //CacheIncludeProcessor
        $this['cacheinclude_processor'] = $this->share(
            function ($c) {
                return new $c['cacheinclude_processor.class'];
            }
        );

        //CacheIncludeConfig
        $this['cacheinclude_config'] = $this->share(
            function ($c) {
                return new $c['cacheinclude_config.class']($c['cacheinclude_config.config']);
            }
        );

        //Default config
        foreach (self::$config as $key => $value) {
            $this[$key] = $value;
        }

        //Extensions
        if (is_array(self::$extensions)) {
            foreach (self::$extensions as $value) {
                $this->extend($value[0], $value[1]);
            }
        }

        //Shared
        if (is_array(self::$shared)) {
            foreach (self::$shared as $value) {
                $this[$value[0]] = $this->share($value[1]);
            }
        }

    }
    /**
     * Returns and instance of the container
     * @return \Heyday\CacheInclude\Container Cache Include Container
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }
    /**
     * Alows the extending of already defined services by the user
     * @param string  $name      Name of service
     * @param Closure $extension Extending function
     */
    public static function addExtension($name, $extension)
    {
        self::$extensions[] = array($name, $extension);
    }
    /**
     * Allows the adding of a shared service by the user
     * @param string  $name   Name of service
     * @param Closure $shared The shared service function
     */
    public static function addShared($name, $shared)
    {
        self::$shared[] = array($name, $shared);
    }
    /**
     * Allows the addition to the default config by the user
     * @param array $config The extending config
     */
    public static function extendConfig($config)
    {
        if (is_array($config)) {
            self::$config = array_merge(self::$config, $config);
        }
    }
}
