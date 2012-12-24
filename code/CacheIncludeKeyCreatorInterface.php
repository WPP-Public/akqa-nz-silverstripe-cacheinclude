<?php

interface CacheIncludeKeyCreatorInterface
{
    public function getKey($name, Controller $controller, $config);
}
