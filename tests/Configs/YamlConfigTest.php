<?php

namespace Heyday\CacheInclude\Configs;

use Symfony\Component\Cache\Simple\ArrayCache;
use Symfony\Component\Yaml\Yaml;

class YamlConfigTest extends \PHPUnit_Framework_TestCase
{
    protected $validYamlData;
    protected $invalidYamlData;

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
"missing colon"
  foo: bar
YAML;
    }

    /**
     * @expectedException \Symfony\Component\Yaml\Exception\ParseException
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

    public function testYamlParseReadFile()
    {
        $path = __DIR__ . '/test.yml';
        file_put_contents($path, $this->validYamlData);
        $key = md5($this->validYamlData);

        $cacheMock = $this->getMockBuilder(ArrayCache::class)
            ->setMethods(array('has', 'get'))
            ->getMock();

        $cacheMock->expects($this->once())
            ->method('has')
            ->with($key)
            ->will($this->returnValue(true));

        $cacheMock->expects($this->once())
            ->method('get')
            ->with($key)
            ->will($this->returnValue(Yaml::parse($this->validYamlData)));

        $config = new YamlConfig($path, $cacheMock);

        $this->assertEquals(
            array(
                'expires' => '+1 week',
                'contains' => array(
                    'Page',
                    'HomePage'
                )
            ),
            $config['Something']
        );

        unlink($path);
    }

    public function testYamlCacheHit()
    {
        $cacheMock = $this->getMockBuilder(ArrayCache::class)
            ->setMethods(array('has', 'get'))
            ->getMock();

        $cacheMock->expects($this->once())
            ->method('has')
            ->will($this->returnValue(true));

        $cacheMock->expects($this->once())
            ->method('get')
            ->will($this->returnValue(Yaml::parse($this->validYamlData)));

        new YamlConfig($this->validYamlData, $cacheMock);
    }

    public function testYamlCacheMiss()
    {
        $cacheMock = $this->getMockBuilder(ArrayCache::class)
            ->setMethods(array('has', 'set'))
            ->getMock();

        $cacheMock->expects($this->once())
            ->method('has')
            ->will($this->returnValue(false));

        $cacheMock->expects($this->once())
            ->method('set')
            ->with(
                md5($this->validYamlData),
                array(
                    'Something' => array(
                        'expires' => '+1 week',
                        'contains' => array(
                            'Page',
                            'HomePage'
                        )
                    )
                )
            );

        new YamlConfig($this->validYamlData, $cacheMock);
    }
}
