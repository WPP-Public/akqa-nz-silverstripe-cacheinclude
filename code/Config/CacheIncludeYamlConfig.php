<?php

use Symfony\Component\Yaml\Yaml;

class CacheIncludeYamlConfig extends CacheIncludeArrayConfig
{
	public function __construct($file, \CacheCache\Cache $cache = null)
	{
		if (!is_readable($file)) {
			throw new InvalidArgumentException("$file is not readable");
		}
		if ($cache instanceof \CacheCache\Cache) {
			if (!($result = $cache->load($file))) {
				$cache->save($result = Yaml::parse($file));
			}
		} else {
			$result = Yaml::parse($file);
		}
		parent::__construct($result);
	}
}