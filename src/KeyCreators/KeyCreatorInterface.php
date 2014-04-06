<?php

namespace Heyday\CacheInclude\KeyCreators;

use Controller;

/**
 * Class KeyCreatorInterface
 * @package Heyday\CacheInclude\KeyCreators
 */
interface KeyCreatorInterface
{
    /**
     * @param              $name
     * @param              $config
     * @return mixed
     */
    public function getKey($name, $config);
}
