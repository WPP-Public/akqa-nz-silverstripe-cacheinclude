<?php

use Heyday\CacheInclude\CacheInclude;
use Heyday\CacheInclude\Processors\ViewableDataProcessor;

/**
 * Class CacheIncludeExtension
 */
class CacheIncludeExtension extends Extension
{
    /**
     * @var Heyday\CacheInclude\CacheInclude
     */
    protected $cache;
    /**
     * @var Heyday\CacheInclude\Processors\ViewableDataProcessor
     */
    protected $processor;
    /**
     * @var array
     */
    private static $run = array();
    /**
     * @param Heyday\CacheInclude\CacheInclude                     $cache
     * @param Heyday\CacheInclude\Processors\ViewableDataProcessor $processor
     */
    public function __construct(
        CacheInclude $cache,
        ViewableDataProcessor $processor
    ) {
        $this->cache = $cache;
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
     * @param $template
     * @return mixed|null
     */
    public function CacheIncludePartial($name, $template)
    {
        $controller = $this->getController();

        return $this->cache->process(
            $name,
            function () use ($template, $controller) {
                return $controller->renderWith(new SSViewer_FromString($template));
            },
            $controller
        );
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
            $this->getController()
        );
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
