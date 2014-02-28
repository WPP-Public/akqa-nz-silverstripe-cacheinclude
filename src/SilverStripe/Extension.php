<?php

namespace Heyday\CacheInclude\SilverStripe;

use Controller;
use Extension as SilverStripeExtension;
use Heyday\CacheInclude\CacheInclude;
use Heyday\CacheInclude\KeyCreators\ControllerBased;
use Heyday\CacheInclude\Processors\ViewableDataProcessor;
use Heyday\CacheInclude\KeyCreators\KeyCreatorInterface;
use InvalidArgumentException;
use SS_HTTPRequest;

/**
 * Class Extension
 * @package Heyday\CacheInclude\SilverStripe
 */
class Extension extends SilverStripeExtension
{
    /**
     * @var \Heyday\CacheInclude\CacheInclude
     */
    protected $cache;
    /**
     * @var \Heyday\CacheInclude\Processors\ViewableDataProcessor
     */
    protected $processor;
    /**
     * @var \Heyday\CacheInclude\KeyCreators\KeyCreatorInterface
     */
    protected $keyCreator;
    /**
     * @var array
     */
    private static $run = array();

    /**
     * @param \Heyday\CacheInclude\CacheInclude                     $cache
     * @param \Heyday\CacheInclude\Processors\ViewableDataProcessor $processor
     * @param KeyCreatorInterface                                   $keyCreator
     */
    public function __construct(
        CacheInclude $cache,
        ViewableDataProcessor $processor,
        KeyCreatorInterface $keyCreator = null
    )
    {
        $this->cache = $cache;
        $this->keyCreator = $keyCreator ?: \Injector::inst()->create('CacheIncludeKeyCreator', $this->getController());
        $this->processor = $processor;
        parent::__construct();
    }

    /**
     * @return Controller
     */
    protected function getController()
    {
        $controller = $this->owner;
        if (!($controller instanceof Controller) || !($controller->getRequest() instanceof SS_HTTPRequest)) {
            $controller = Controller::curr();
        }

        return $controller;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function CacheInclude($name)
    {
        return $this->cache->process(
            $name,
            $this->processor->setContext($this->owner),
            $this->keyCreator
        );
    }

    /**
     * Allows the use of a <% cache 'ConfigName' %><% end_cache %> syntax in templates
     * @param $res
     * @throws \InvalidArgumentException
     * @return string
     */
    public static function cacheTemplate(&$res)
    {
        if (!isset($res['Arguments']) || !isset($res['Arguments'][0])) {
            throw new InvalidArgumentException('A config name must be passed into <% cache %>');
        }
        
        if (isset($res['Arguments'][1])) {
            $cacheIncludeServiceName = $res['Arguments'][1]['text'];
        } else {
            $cacheIncludeServiceName = "'CacheInclude'";
        }

        if (isset($res['Arguments'][2])) {
            $cacheIncludeKeyCreatorServiceName = $res['Arguments'][2]['text'];
        } else {
            $cacheIncludeKeyCreatorServiceName = "'CacheIncludeKeyCreator'";
        }

        return <<<PHP
\$val .= Injector::inst()->get($cacheIncludeServiceName)->process(
   {$res['Arguments'][0]['text']},
   function () use (\$scope) {
        \$val = '';
        {$res['Template']['php']}        return \$val;
   },
   Injector::inst()->create($cacheIncludeKeyCreatorServiceName, Controller::curr())
);
PHP;
    }

    /**
     * Remove invalid caches
     */
    public function onAfterWrite()
    {
        $this->onChange();
    }

    /**
     * Remove invalid caches
     */
    public function onAfterDelete()
    {
        $this->onChange();
    }

    /**
     * Remove invalid caches
     */
    public function onChange()
    {
        if (!isset(self::$run[$this->owner->ClassName])) {

            self::$run[$this->owner->ClassName] = true;

            $names = array();

            foreach ($this->cache->getConfig() as $name => $config) {

                if (isset($config['contains']) && is_array($config['contains'])) {

                    foreach ($config['contains'] as $class) {

                        if ($this->owner instanceof $class) {

                            $names[] = $name;

                            break;

                        }

                    }

                }

            }

            if (count($names) > 0) {

                foreach ($names as $name) {

                    $this->cache->flushByName($name);

                }

            }

        }
    }

    /**
     *
     */
    public function extraStatics($class = null, $extension = null)
    {

    }
}
