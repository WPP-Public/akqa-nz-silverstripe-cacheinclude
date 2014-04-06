<?php

namespace Heyday\CacheInclude\Configs;

use org\bovigo\vfs\vfsStream;
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
s
s
s
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
        vfsStream::setup(
            'root',
            null,
            array(
                'test.yml' => $this->validYamlData
            )
        );

        $key = md5($this->validYamlData);

        $cacheMock = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(array('contains', 'fetch'))
            ->getMockForAbstractClass();

        $cacheMock->expects($this->once())
            ->method('contains')
            ->with($key)
            ->will($this->returnValue(true));

        $cacheMock->expects($this->once())
            ->method('fetch')
            ->with($key)
            ->will($this->returnValue(Yaml::parse($this->validYamlData)));

        $config = new YamlConfig(
            vfsStream::url('root/test.yml'),
            $cacheMock
        );

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
    }

    public function testYamlCacheHit()
    {
        $cacheMock = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(array('contains', 'fetch'))
            ->getMockForAbstractClass();

        $cacheMock->expects($this->once())
            ->method('contains')
            ->will($this->returnValue(true));

        $cacheMock->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue(Yaml::parse($this->validYamlData)));

        new YamlConfig($this->validYamlData, $cacheMock);
    }

    public function testYamlCacheMiss()
    {
        $cacheMock = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(array('contains', 'save'))
            ->getMockForAbstractClass();

        $cacheMock->expects($this->once())
            ->method('contains')
            ->will($this->returnValue(false));

        $cacheMock->expects($this->once())
            ->method('save')
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
