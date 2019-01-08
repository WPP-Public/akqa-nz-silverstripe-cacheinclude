<?php

namespace Heyday\CacheInclude\Configs;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Yaml\Yaml;

class YamlConfig extends ArrayConfig
{
    /**
     * @param string $yaml
     * @param CacheInterface $cache
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function __construct($yaml, CacheInterface $cache = null)
    {
        if ($cache instanceof CacheInterface) {
            if (strpos($yaml, "\n") === false && is_file($yaml)) {
                $yaml = file_get_contents($yaml);
            }

            $key = md5($yaml);
            if ($cache->has($key)) {
                $result = $cache->get($key);
            } else {
                $result = Yaml::parse($yaml);
                $cache->set($key, $result);
            }
        } else {
            $result = Yaml::parse($yaml);
        }

        if (!$result) {
            $result = [];
        }

        parent::__construct($result);
    }
}
