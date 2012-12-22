<?php

class CacheIncludeExtension extends Extension
{

    private static $_run = array();

    /**
     * Takes a yaml file and loads it into the config
     * @param string $file
     */
    public static function loadConfig($file)
    {

        $cachefile = $file . '.cache';

        if (file_exists($cachefile) && !isset($_GET['flush'])) {

            $yaml = unserialize(file_get_contents($cachefile));

        }

        if (!isset($yaml) || !is_array($yaml)) {

            require_once 'thirdparty/spyc/spyc.php';

            $yaml = Spyc::YAMLLoad($file);

            file_put_contents($cachefile, serialize($yaml));

        }

        self::setConfig($yaml);

    }
    /**
     * Deletes all cache files
     */
    // public static function clearAll()
    // {

    //     foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::$_directory)) as $file) {
    //         unlink($file);
    //     }

    // }
    /**
     * Clears all cache files for a particular template
     * @param string $template
     */
    // public static function clearTemplate($template, $force = false)
    // {

    //     if (self::$_delayed_processing && !$force) {

    //         CacheIncludeQueueItem::add($template);

    //     } else {

    //         $templates = glob(self::path('*' . $template));
            
    //         if (is_array($templates) && count($templates) > 0) {
                
    //             foreach ($templates as $file) {

    //                 unlink($file);

    //             }
                
    //         }

    //     }

    // }

    // public static function clearMember($memberId)
    // {

    //     foreach (glob(self::$_directory . '/Members_' . $memberId . '*.cache') as $file) {

    //         unlink($file);

    //     }

    // }

    // public static function clearFolder($folder = '')
    // {

    //     if (is_dir(self::$directory . '/' . $folder)) {

    //         foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::$_directory . '/' . $folder)) as $file) {
    //             unlink($file);
    //         }

    //     }

    // }

    // public static function getController()
    // {

    //     if (!self::$_controller) {

    //         self::$_controller = Controller::curr();

    //     }

    //     return self::$_controller;

    // }

    public function CacheInclude($name)
    {
        $this->dic['cacheinclude']->process(
            $name,
            $this->dic['cacheinclude_processor']->setContext($this->owner),
            $this->owner instanceof Controller ? $this->owner : Controller::curr()
        );
    }


    public function CacheIncludePartial($name, $template)
    {
        return $this->CacheInclude(trim($name), false, new SSViewer_FromString(str_replace(array(
            '{#',
            '#}',
            '{{',
            '}}',
            '{|',
            '|}',
            '{%c%}'
        ), array(
            '<%',
            '%>',
            '$',
            '',
            '(',
            ')',
            ','
        ), $template)));
    }

    protected function cacheContent($template, $function = false)
    {

        if ($function && is_string($template) && $this->owner->hasMethod($template)) {

            $result = $this->owner->$template();

            if ($result instanceof ViewableData) {

                return $result->forTemplate();

            } else {

                return $result;

            }

        }

        return $this->owner->renderWith($template);

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

        if (!isset(self::$_run[$this->owner->ClassName])) {

            self::$_run[$this->owner->ClassName] = true;

            $templates = array();

            foreach (self::$_config as $template => $config) {

                if (
                    isset($config['contains'])
                    &&
                    is_array($config['contains'])
                ) {

                    foreach ($config['contains'] as $class) {

                        if ($this->owner instanceof $class) {

                            $templates[] = $template;

                            break;

                        }
                    }

                }

            }

            if (count($templates) > 0) {

                foreach ($templates as $template) {

                    self::clearTemplate($template);

                }

            }

        }

    }

    public function extraStatics()
    {

    }

}
