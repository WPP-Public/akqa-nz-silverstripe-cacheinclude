<?php

namespace Heyday\CacheInclude\SilverStripe;

use Extension;
use Heyday\CacheInclude\CacheInclude;
use Heyday\CacheInclude\ExpressionLanguage;
use Psr\Log\LoggerInterface;

/**
 * Class Extension
 * @package Heyday\CacheInclude\SilverStripe
 */
class InvalidationExtension extends Extension
{
    /**
     * @var \Heyday\CacheInclude\CacheInclude
     */
    protected $cache;
    /**
     * @var \Heyday\CacheInclude\ExpressionLanguage
     */
    protected $expressionLanguage;

    /**
     * @param \Heyday\CacheInclude\CacheInclude $cache
     * @param \Heyday\CacheInclude\ExpressionLanguage $expressionLanguage
     */
    public function __construct(
        CacheInclude $cache,
        ExpressionLanguage $expressionLanguage
    )
    {
        $this->cache = $cache;
        $this->expressionLanguage = $expressionLanguage;
        parent::__construct();
    }

    /**
     * @return void
     */
    public function onAfterPublish()
    {
        $this->onChange('publish');
    }

    /**
     * @return void
     */
    public function onAfterUnpublish()
    {
        $this->onChange('unpublish');
    }

    /**
     * @return void
     */
    public function onAfterWrite()
    {
        $this->onChange('write');
    }

    /**
     * @return void
     */
    public function onAfterDelete()
    {
        $this->onChange('delete');
    }

    /**
     * @param $action
     */
    protected function onChange($action)
    {
        $vars = array(
            'item' => $this->owner,
            'action' => $action
        );

        $logger = $this->cache->getLogger();

        // TODO: A bit complex. Needs refactor
        foreach ($this->cache->getConfig() as $name => $inst) {
            $hasContainsRules = isset($inst['contains']) && is_array($inst['contains']);
            $hasInvalidationRules = isset($inst['invalidation_rules']) && is_array($inst['invalidation_rules']);
            
            // Check to see if there are contains rules, and if there is
            // we want to ensure that the invalidation rules are only processed
            // if the class at least passes the invalidation rules
            if ($hasContainsRules) {
                $contains = false;
                foreach ($inst['contains'] as $contain) {
                    if ($this->owner instanceof $contain) {
                        $contains = $contain;
                        break;
                    }
                }
                
                // We don't want to do any more processing if the contains
                // rules didn't even pass
                if (!$contains) {
                    continue;
                }
            } else {
                $contains = true;
            }
            
            if ($hasInvalidationRules) {
                foreach ($inst['invalidation_rules'] as $rule) {
                    if ($this->expressionLanguage->evaluate($rule, $vars) && $contains) {
                        $this->invalidate(
                            $name,
                            sprintf(
                                "Cache name '%s' invalidated by rule '%s'",
                                $name,
                                $rule
                            ),
                            $logger
                        );
                        break;
                    }
                }
            } elseif ($hasContainsRules && $contains) {
                // If there aren't any invalidation rules then invalidate the cache
                // simply based on the contains rules passing
                $this->invalidate(
                    $name,
                    sprintf(
                        "Cache name '%s' invalidated as it contains '%s'",
                        $name,
                        $contains
                    ),
                    $logger
                );
            }
        }
    }

    /**
     * Invalidates a cache by a certain name, and logs if available
     * @param $name
     * @param $message
     * @param null $logger
     */
    protected function invalidate($name, $message, $logger = null)
    {
        $this->cache->flushByName($name);
        if ($logger) {
            $logger->info($message);
        }
    }

    /**
     * @param null $class
     * @param null $extension
     */
    public function extraStatics($class = null, $extension = null)
    {

    }
}
