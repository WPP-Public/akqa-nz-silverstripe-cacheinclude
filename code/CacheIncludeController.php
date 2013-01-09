<?php

class CacheIncludeController extends Controller
{

    public function init()
    {

        if (!defined('STDIN')) {

            exit;

        }

        parent::init();

    }

    public function clearAll()
    {

        $dic = Heyday\CacheInclude\Container::getInstance();

        $dic['cacheinclude']->flushAll();

    }

    public function clearTemplate()
    {

        if (isset($_GET['args'][0])) {

            $dic = Heyday\CacheInclude\Container::getInstance();

            $dic['cacheinclude']->flushByName($_GET['args'][0]);

        }

    }

}
