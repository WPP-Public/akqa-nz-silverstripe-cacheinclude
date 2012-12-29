<?php

namespace Heyday\CacheInclude\Configs;

use Symfony\Component\Yaml\Yaml;
use CacheCache\Cache;

class YamlConfig extends ArrayConfig
{
    public function __construct($yaml, Cache $cache = null)
    {
        if ($cache instanceof Cache) {
            if (!($result = $cache->load(md5($yaml)))) {
                $cache->save($result = Yaml::parse($yaml));
            }
        } else {
            $result = Yaml::parse($yaml);
        }
        parent::__construct($result);
    }
}
