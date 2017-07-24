<?php

namespace Heyday\CacheInclude\SilverStripe;

use Heyday\CacheInclude\CacheInclude;
use SilverStripe\Control\CliController;

/**
 * Class Controller
 * @package Heyday\CacheInclude\SilverStripe
 */
class Controller extends CliController
{
    /**
     * @var \Heyday\CacheInclude\CacheInclude
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
     * Clears a specific template
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
