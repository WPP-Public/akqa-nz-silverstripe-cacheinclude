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
     * @param             $name
     * @param \Controller $controller
     * @param             $config
     * @return mixed
     */
    public function getKey($name, Controller $controller, $config);
}
