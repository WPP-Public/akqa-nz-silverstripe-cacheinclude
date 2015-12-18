<?php

namespace Heyday\CacheInclude;

use Doctrine\Common\Cache\ArrayCache;
use Heyday\CacheInclude\Configs\ArrayConfig;

class CacheIncludeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    protected $cacheMock;
    /**
     * @var \Heyday\CacheInclude\CacheInclude
     */
    protected $cacheinclude;

    /**
     * @var \Heyday\CacheInclude\KeyCreators\KeyCreatorInterface
     */
    protected $keyCreatorMock;

    protected function setUp()
    {
        $this->cacheMock = new ArrayCache();
        $this->keyCreatorMock = $this->getMock('Heyday\CacheInclude\KeyCreators\KeyCreatorInterface');
        $this->cacheinclude = new CacheInclude(
            $this->cacheMock,
            new ArrayConfig(array(
                'test' => array(
                    'expires' => '+1 week'
                )
            ))
        );
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
            'member'  => true,
            'expires' => false
        );
        $this->cacheinclude->setDefaultConfig($config);
        $this->assertEquals($config, $this->cacheinclude->getDefaultConfig());
    }

    public function testCombinedConfig()
    {
        $this->assertEquals(
            array(
                'context' => 'no',
                'member'  => false,
                'expires' => '+1 week'
            ),
            $this->cacheinclude->getCombinedConfig('test')
        );
    }
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The argument $processor must be an instance of ProcessorInterface or a callable
     */
    public function testProcessException()
    {
        $this->cacheinclude->process('test', array(), $this->keyCreatorMock);
    }

    public function testProcessDisabled()
    {
        $this->cacheinclude->setEnabled(false);
        $this->assertEquals(
            'hello',
            $this->cacheinclude->process(
                'test',
                function () {
                    return 'hello';
                },
                $this->keyCreatorMock
            )
        );
    }

    public function testProcess()
    {
        $this->keyCreatorMock
            ->expects($this->any())
            ->method('getKey')
            ->will($this->returnValue(array('testkey')));

        $i = 0;

        $i = $this->cacheinclude->process(
            'test',
            function ($name) use ($i) {
                $i++;

                return $i;
            },
            $this->keyCreatorMock
        );

        $this->assertEquals(1, $i);

        $i = $this->cacheinclude->process(
            'test',
            function ($name) use ($i) {
                $i++;

                return $i;
            },
            $this->keyCreatorMock
        );

        $this->assertEquals(1, $i);

        $this->cacheinclude->setForceExpire(true);

        $i = $this->cacheinclude->process(
            'test',
            function ($name) use ($i) {
                $i++;

                return $i;
            },
            $this->keyCreatorMock
        );

        $this->assertEquals(2, $i);

        $i = $this->cacheinclude->process(
            'test',
            function ($name) use ($i) {
                $i++;

                return $i;
            },
            $this->keyCreatorMock
        );

        $this->assertEquals(3, $i);

        $this->cacheinclude->setForceExpire(false);

        $i = $this->cacheinclude->process(
            'test',
            function ($name) use ($i) {
                $i++;

                return $i;
            },
            $this->keyCreatorMock
        );

        $this->assertEquals(4, $i);

        $i = $this->cacheinclude->process(
            'test',
            function ($name) use ($i) {
                $i++;

                return $i;
            },
            $this->keyCreatorMock
        );

        $this->assertEquals(4, $i);

    }

    public function testForceExpire()
    {
        $this->cacheinclude->setForceExpire(true);
        $this->assertTrue($this->cacheinclude->getForceExpire());
    }

    public function testCreatingAndDeletingLockKey()
    {
        $keyName = "test";
        $this->assertTrue($this->cacheinclude->createLockForName($keyName),
          "Writing lock key");
        $this->assertTrue($this->cacheinclude->checkLockForName($keyName),
          "Checking lock key");
        $this->assertTrue($this->cacheinclude->releaseLockForName($keyName),
          "Releasing lock key");
    }

}
