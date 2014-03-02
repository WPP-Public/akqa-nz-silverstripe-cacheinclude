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

        foreach ($this->cache->getConfig() as $name => $inst) {
            if (isset($inst['invalidation_rules'])) {
                foreach ($inst['invalidation_rules'] as $rule) {
                    if ($this->expressionLanguage->evaluate($rule, $vars)) {
                        $this->cache->flushByName($name);
                        if ($logger = $this->cache->getLogger()) {
                            $logger->info(sprintf(
                                "Cache '%s' invalidated by rule '%s'",
                                $name,
                                $rule
                            ));
                        }
                        break;
                    }
                }
            }
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
