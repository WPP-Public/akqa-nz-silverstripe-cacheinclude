<?php

namespace Heyday\CacheInclude\SilverStripe;

use Heyday\CacheInclude\CacheInclude;
use SilverStripe\Dev\BuildTask as SilverStripeBuildTask;
use SilverStripe\Versioned\Versioned;

class BuildTask extends SilverStripeBuildTask
{
    /**
     * @var CacheInclude
     */
    protected $cache;

    private static $segment = 'CacheIncludeClearTask';

    protected $title = 'Cache Include clear task';

    protected $description = 'Clear all cache include caches (with all=1), or named ones (with name=foo)';

    /**
     * @param CacheInclude $cache
     */
    public function __construct(CacheInclude $cache)
    {
        $this->cache = $cache;
        parent::__construct();
    }

    public function run($request)
    {
        $all = $request->getVar('all');
        $name = $request->getVar('name');

        if ($all === null && $name === null) {
            echo 'You must specify a cache with name=cachename, or flush all caches with all=1' . PHP_EOL;
            exit;
        }

        if ($all !== null) {
            $callback = function($stage) {
                echo "Flushing all caches for stage: {$stage}" . PHP_EOL;
                $this->cache->flushAll();
            };
        } else {
            $callback = function($stage) use ($name) {
                echo "Flushing named cache '{$name}' for stage: {$stage}" . PHP_EOL;
                $this->cache->flushByName($name);
            };
        }

        // Flush in both draft + live
        Versioned::withVersionedMode(function () use ($callback) {
            foreach ([Versioned::LIVE, Versioned::DRAFT] as $stage) {
                Versioned::set_stage($stage);
                $callback($stage);
            }
        });
    }
}