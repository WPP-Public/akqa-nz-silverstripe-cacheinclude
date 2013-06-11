<?php

use Heyday\CacheInclude\Container;

/**
 * Class CacheIncludeController
 */
class CacheIncludeController extends \CliController
{
    static $allowed_actions = array(
        'index',
        'clearAll',
        'clearTemplate'
    );
    /**
     * Holds the dependency injection container
     * @var
     */
    protected $container;
    /**
     *
     */
    public function __construct()
    {
        $this->container = Container::getInstance();
        parent::__construct();
    }
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
    public function index()
    {
        return <<<INFO
Usage:
sake cache-include #help
sake cache-include/clearAll #clear all templates
sake cache-include/clearTemplate/TemplateName #clear specific template

INFO;
    }
    /**
     * Clears all caches
     */
    public function clearAll()
    {
        $this->getService('cacheinclude')->flushAll();
        return 'Done' . PHP_EOL;
    }
    /**
     *
     */
    public function clearTemplate()
    {
        if ($this->request->param('ID')) {
            $this->getService('cacheinclude')->flushByName($this->request->param('ID'));
            return 'Done' . PHP_EOL;
        } else {
            return 'You must specify a template:' . PHP_EOL . $this->index();
        }
    }
    protected function getService($name)
    {
        return $this->container[$name];
    }
}
