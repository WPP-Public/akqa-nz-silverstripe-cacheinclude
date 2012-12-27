<?php

namespace Heyday\CacheInclude;

interface KeyCreatorInterface
{
    public function getKey($name, \Controller $controller, $config);
}
