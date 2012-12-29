<?php

namespace Heyday\CacheInclude\Tests;

use Heyday\CacheInclude\Configs\YamlConfig;
use CacheCache\Cache;
use CacheCache\Backends\Memory;

class YamlConfigTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->validYamlData = <<<YAML
Something:
  expires: +1 week
  contains:
    - Page
    - HomePage
YAML;
        $this->invalidYamlData = <<<YAML
s
s
s
YAML;
        $this->cache = new Cache(new Memory);
    }

    protected function tearDown()
    {
        $this->validYamlData = null;
        $this->invalidYamlData = null;
    }
    /**
     * @expectedException Symfony\Component\Yaml\Exception\ParseException
     */
    public function testYamlException()
    {
        new YamlConfig($this->invalidYamlData);
    }

    public function testYamlParse()
    {
        $config = new YamlConfig($this->validYamlData);

        $this->assertEquals(array(
            'expires' => '+1 week',
            'contains' => array(
                'Page',
                'HomePage'
            )
        ), $config['Something']);
    }

    public function testYamlCache()
    {
        new YamlConfig($this->validYamlData, $this->cache);
        $this->assertEquals(
            array(
                'Something' => array(
                    'expires' => '+1 week',
                    'contains' => array(
                        'Page',
                        'HomePage'
                    )
                )
            ),
            $this->cache->get(md5($this->validYamlData))
        );
    }
}
