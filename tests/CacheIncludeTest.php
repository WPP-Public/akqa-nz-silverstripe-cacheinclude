<?php

namespace Heyday\CacheInclude;

use Heyday\CacheInclude\Configs\ArrayConfig;
use Heyday\CacheInclude\KeyCreators\KeyCreatorInterface;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use Symfony\Component\Cache\Simple\ArrayCache;

class CacheIncludeTest extends SapphireTest
{
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
        Injector::inst()->registerService(new ArrayCache(), CacheInterface::class . '.CacheInclude');
        $this->keyCreatorMock = $this->getMockBuilder(KeyCreatorInterface::class)
            ->getMock();
        $this->cacheinclude = new CacheInclude(
            new ArrayConfig(array(
                'test' => array(
                    'expires' => '+1 week'
                )
            ))
        );

        parent::setUp();
    }

    public function testEnabled()
    {
        $this->assertTrue($this->cacheinclude->getEnabled());
        $this->cacheinclude->setEnabled(false);
        $this->assertFalse($this->cacheinclude->getEnabled());
    }

    public function testSetDefaultConfig()
    {
        $config = [
            'context' => 'no',
            'member'  => true,
            'expires' => false
        ];
        $this->cacheinclude->setDefaultConfig($config);
        $this->assertEquals($config, $this->cacheinclude->getDefaultConfig());
    }

    public function testCombinedConfig()
    {
        $this->assertEquals(
            [
                'context' => 'no',
                'member'  => false,
                'expires' => '+1 week'
            ],
            $this->cacheinclude->getCombinedConfig('test')
        );
    }
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The argument $processor must be an instance of ProcessorInterface or a callable
     */
    public function testProcessException()
    {
        $this->cacheinclude->process('test', [], $this->keyCreatorMock);
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
            ->will($this->returnValue(['testkey']));

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
}
