<?php

use Heyday\CacheInclude\Container;

/**
 * Class CacheIncludeController
 */
class CacheIncludeController extends CliController
{
    /**
     * Ensure clear actions can only be run from the command line
     */
    public function init()
    {
        parent::init();
        if (!Director::is_cli() && !Permission::check('ADMIN')) {
            return Security::permissionFailure();
        }
    }
    /**
     *
     */
    public function clearAll()
    {
        $dic = $this->getContainer();
        $dic['cacheinclude']->flushAll();
    }
    /**
     *
     */
    public function clearTemplate()
    {
        if (isset($_GET['args'][0])) {
            $dic = $this->getContainer();
            $dic['cacheinclude']->flushByName($_GET['args'][0]);
        }
    }
    /**
     * @return Container
     */
    protected function getContainer()
    {
        return Container::getInstance();
    }
}
