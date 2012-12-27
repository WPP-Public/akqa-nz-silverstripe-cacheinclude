<?php

namespace Heyday\CacheInclude\Configs;

use Symfony\Component\Yaml\Yaml;
use CacheCache\Cache;

class YamlConfig extends ArrayConfig
{
    public function __construct($file, Cache $cache = null)
    {
        if (!is_readable($file)) {
            throw new \InvalidArgumentException("$file is not readable");
        }
        if ($cache instanceof Cache) {
            if (!($result = $cache->load($file))) {
                $cache->save($result = Yaml::parse($file));
            }
        } else {
            $result = Yaml::parse($file);
        }
        parent::__construct($result);
    }
}
