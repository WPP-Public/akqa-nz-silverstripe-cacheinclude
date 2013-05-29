<?php

use Heyday\CacheInclude\Container;

/**
 * Class CacheIncludeExtension
 */
class CacheIncludeExtension extends Extension
{
    /**
     * @var array
     */
    private static $run = array();
    /**
     * @var
     */
    protected $container;
    /**
     * Get a container and set it
     */
    public function __construct()
    {
        parent::__construct();
        $this->container = Container::getInstance();
    }
    /**
     * @param $name
     * @return mixed
     */
    public function CacheInclude($name)
    {
        return $this->container['cacheinclude']->process(
            $name,
            $this->container['cacheinclude_processor']->setContext($this->owner),
            $this->owner instanceof Controller ? $this->owner : Controller::curr()
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

            $cacheinclude = $this->container['cacheinclude'];

            foreach ($cacheinclude->getConfig() as $name => $config) {

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

                    $cacheinclude->flushByName($name);

                }

            }

        }
    }
    /**
     *
     */
    public function extraStatics()
    {

    }
}
