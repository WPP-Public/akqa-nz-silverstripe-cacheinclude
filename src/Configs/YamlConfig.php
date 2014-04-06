<?php

namespace Heyday\CacheInclude\Configs;

use Doctrine\Common\Cache\CacheProvider;
use Symfony\Component\Yaml\Yaml;

/**
 * Class YamlConfig
 * @package Heyday\CacheInclude\Configs
 */
class YamlConfig extends ArrayConfig
{
    /**
     * @param               $yaml
     * @param CacheProvider $cache
     */
    public function __construct($yaml, CacheProvider $cache = null)
    {
        if ($cache instanceof CacheProvider) {
            if (strpos($yaml, "\n") === false && is_file($yaml)){
                $yaml = file_get_contents($yaml);
            }

            $key = md5($yaml);

            if ($cache->contains($key)) {
                $result = $cache->fetch($key);
            } else {
                $result = Yaml::parse($yaml);
                $cache->save($key, $result);
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
