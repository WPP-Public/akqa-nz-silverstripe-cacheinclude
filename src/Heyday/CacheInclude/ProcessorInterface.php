<?php

namespace Heyday\CacheInclude;

interface ProcessorInterface
{
    public function __invoke($name);
}
