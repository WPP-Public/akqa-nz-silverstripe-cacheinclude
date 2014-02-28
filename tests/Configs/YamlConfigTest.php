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

    protected function tearDown()
    {
        $this->validYamlData = null;
        $this->invalidYamlData = null;
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

        $itemMock = $this->getMockBuilder('Stash\Item')->disableOriginalConstructor()->getMock();

        $itemMock->expects($this->once())
            ->method('get')
            ->will($this->returnValue(Yaml::parse($this->validYamlData)));

        $itemMock->expects($this->once())
            ->method('isMiss')
            ->will($this->returnValue(false));

        $cacheMock = $this->getMockBuilder('Stash\Pool')->disableOriginalConstructor()->getMock();

        $cacheMock->expects($this->once())
            ->method('getItem')
            ->with(md5($this->validYamlData))
            ->will($this->returnValue($itemMock));

        $config = new YamlConfig(vfsStream::url('root/test.yml'), $cacheMock);

        $this->assertEquals(array(
            'expires' => '+1 week',
            'contains' => array(
                'Page',
                'HomePage'
            )
        ), $config['Something']);
    }

    public function testYamlCacheHit()
    {
        $itemMock = $this->getMockBuilder('Stash\Item')->disableOriginalConstructor()->getMock();

        $itemMock->expects($this->once())
            ->method('get');

        $itemMock->expects($this->once())
            ->method('isMiss')
            ->will($this->returnValue(false));

        $itemMock->expects($this->never())
            ->method('set');

        $cacheMock = $this->getMockBuilder('Stash\Pool')->disableOriginalConstructor()->getMock();

        $cacheMock->expects($this->once())
            ->method('getItem')
            ->will($this->returnValue($itemMock));

        new YamlConfig($this->validYamlData, $cacheMock);
    }

    public function testYamlCacheMiss()
    {
        $itemMock = $this->getMockBuilder('Stash\Item')->disableOriginalConstructor()->getMock();

        $itemMock->expects($this->once())
            ->method('get');

        $itemMock->expects($this->once())
            ->method('isMiss')
            ->will($this->returnValue(true));

        $itemMock->expects($this->once())
            ->method('set')
            ->with(
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

        $cacheMock = $this->getMockBuilder('Stash\Pool')->disableOriginalConstructor()->getMock();

        $cacheMock->expects($this->once())
            ->method('getItem')
            ->will($this->returnValue($itemMock));

        new YamlConfig($this->validYamlData, $cacheMock);
    }
}
