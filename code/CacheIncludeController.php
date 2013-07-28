<?php

use Heyday\CacheInclude\CacheInclude;

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
     * @var Heyday\CacheInclude\CacheInclude
     */
    protected $cache;
    /**
     * @param CacheInclude $cache
     */
    public function __construct(CacheInclude $cache)
    {
        $this->cache = $cache;
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
        $this->cache->flushAll();
        return 'Done' . PHP_EOL;
    }
    /**
     *
     */
    public function clearTemplate()
    {
        if ($this->request->param('ID')) {
            $this->cache->flushByName($this->request->param('ID'));
            return 'Done' . PHP_EOL;
        } else {
            return 'You must specify a template:' . PHP_EOL . $this->index();
        }
    }
}
