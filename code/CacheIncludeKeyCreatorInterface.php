<?php

interface CacheIncludeKeyCreatorInterface
{
    public function getKeyParts($controller, $config);
}
