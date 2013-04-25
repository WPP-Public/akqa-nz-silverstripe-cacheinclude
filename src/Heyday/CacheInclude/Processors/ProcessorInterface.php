<?php

namespace Heyday\CacheInclude\Processors;

/**
 * Class ProcessorInterface
 * @package Heyday\CacheInclude\Processors
 */
interface ProcessorInterface
{
    /**
     * @param $name
     * @return mixed
     */
    public function __invoke($name);
}
