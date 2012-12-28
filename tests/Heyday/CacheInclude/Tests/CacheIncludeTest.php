<?php

namespace Heyday\CacheInclude\Tests;

use CacheCache\Cache;
use CacheCache\Backends\Memory;
use Heyday\CacheInclude\Configs\ArrayConfig;
use Heyday\CacheInclude\CacheInclude;

class CacheIncludeTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->cacheinclude = new CacheInclude(
            $this->cache = new Cache(new Memory),
            new TestKeyCreator,
            new ArrayConfig(array(
                'test' => array(
                    'expires' => '+1 week'
                )
            ))
        );
    }

    protected function tearDown()
    {
        $this->cacheinclude = null;
    }

    public function testEnabled()
    {
        $this->assertTrue($this->cacheinclude->getEnabled());
        $this->cacheinclude->setEnabled(false);
        $this->assertFalse($this->cacheinclude->getEnabled());
    }

    public function testSetDefaultConfig()
    {
        $config = array(
            'context' => 'no',
            'member' => true,
            'expires' => false
        );
        $this->cacheinclude->setDefaultConfig($config);
        $this->assertEquals($config, $this->cacheinclude->getDefaultConfig());
    }

    public function testCombinedConfig()
    {
        $this->assertEquals(array(
            'context' => 'no',
            'member' => false,
            'expires' => '+1 week'
        ), $this->cacheinclude->getCombinedConfig('test'));
    }
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The argument $processor must be an instance of ProcessorInterface or a callable
     */
    public function testProcessException()
    {
        $this->cacheinclude->process('test', array(), new \Controller);
    }

    public function testProcess()
    {

    }
}

class TestKeyCreator implements \Heyday\CacheInclude\KeyCreatorInterface
{
    public function getKey($name, \Controller $controller, $config)
    {
        return 'testkey';
    }
}
