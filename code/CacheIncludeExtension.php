<?php

class CacheIncludeExtension extends Extension
{

    private static $run = array();

    public function __get($name)
    {
        if ($name == 'dic') {
            return $this->dic = \Heyday\CacheInclude\Container::getInstance();
        } else {
            return parent::__get($name);
        }
    }

    public function CacheInclude($name)
    {
        return $this->dic['cacheinclude']->process(
            $name,
            $this->dic['cacheinclude_processor']->setContext($this->owner),
            $this->owner instanceof Controller ? $this->owner : Controller::curr()
        );
    }

    public function onAfterWrite()
    {
        $this->onChange();
    }

    public function onAfterDelete()
    {
        $this->onChange();
    }

    public function onChange()
    {
        if (!isset(self::$run[$this->owner->ClassName])) {

            self::$run[$this->owner->ClassName] = true;

            $names = array();

            $cacheinclude = $this->dic['cacheinclude'];

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

    public function extraStatics()
    {

    }

}
