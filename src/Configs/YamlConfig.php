<?php

namespace Heyday\CacheInclude\Configs;

use Stash\Pool;
use Symfony\Component\Yaml\Yaml;

/**
 * Class YamlConfig
 * @package Heyday\CacheInclude\Configs
 */
class YamlConfig extends ArrayConfig
{
    /**
     * @param            $yaml
     * @param Pool $cache
     */
    public function __construct($yaml, Pool $cache = null)
    {
        if ($cache instanceof Pool) {
            if (strpos($yaml, "\n") === false && is_file($yaml)){
                $yaml = file_get_contents($yaml);
            }
            $item = $cache->getItem(md5($yaml));
            $result = $item->get();
            if ($item->isMiss()) {
                $item->set($result = Yaml::parse($yaml));
            }
        } else {
            $result = Yaml::parse($yaml);
        }

        if (!isset($result)) {
            $result = array();
        }

        parent::__construct($result);
    }
}
