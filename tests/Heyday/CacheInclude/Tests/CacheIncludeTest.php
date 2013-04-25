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
        $this->cache = null;
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

    public function testProcessDisabled()
    {
        $this->cacheinclude->setEnabled(false);
        $this->assertEquals('hello', $this->cacheinclude->process(
            'test',
            function () {
                return 'hello';
            },
            new \Controller
        ));
    }

    public function testProcess()
    {
        $i = 0;

        $i = $this->cacheinclude->process(
            'test',
            function ($name) use ($i) {
                $i++;

                return $i;
            },
            new \Controller
        );

        $this->assertEquals(1, $i);

        $i = $this->cacheinclude->process(
            'test',
            function ($name) use ($i) {
                $i++;

                return $i;
            },
            new \Controller
        );

        $this->assertEquals(1, $i);

        $this->cacheinclude->setForceExpire(true);

        $i = $this->cacheinclude->process(
            'test',
            function ($name) use ($i) {
                $i++;

                return $i;
            },
            new \Controller
        );

        $this->assertEquals(2, $i);

        $i = $this->cacheinclude->process(
            'test',
            function ($name) use ($i) {
                $i++;

                return $i;
            },
            new \Controller
        );

        $this->assertEquals(3, $i);

        $this->cacheinclude->setForceExpire(false);

        $i = $this->cacheinclude->process(
            'test',
            function ($name) use ($i) {
                $i++;

                return $i;
            },
            new \Controller
        );

        $this->assertEquals(4, $i);

        $i = $this->cacheinclude->process(
            'test',
            function ($name) use ($i) {
                $i++;

                return $i;
            },
            new \Controller
        );

        $this->assertEquals(4, $i);

    }

    public function testForceExpire()
    {
        $this->cacheinclude->setForceExpire(true);
        $this->assertTrue($this->cacheinclude->getForceExpire());
    }
}

class TestKeyCreator implements \Heyday\CacheInclude\KeyCreators\KeyCreatorInterface
{
    public function getKey($name, \Controller $controller, $config)
    {
        return 'testkey';
    }
}
