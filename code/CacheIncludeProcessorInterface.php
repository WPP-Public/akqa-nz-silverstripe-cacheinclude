<?php

interface CacheIncludeProcessorInterface
{
	public function __invoke($name, \ViewableData $context, \Controller $controller);
}