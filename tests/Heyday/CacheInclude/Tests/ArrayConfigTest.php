<?php

namespace Heyday\CacheInclude\Tests;

use Heyday\CacheInclude\Configs\ArrayConfig;

class ArrayConfigTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->data = array(
            'One',
            'Two'
        );
    }

    protected function tearDown()
    {
        $this->data = null;
    }

    public function testIteration()
    {
        $config = new ArrayConfig($this->data);
        $i = 0;
        foreach ($config as $key => $val) {
            $this->assertEquals($this->data[$key], $val);
        }
    }
    /**
     * @expectedException Exception
     * @expectedExceptionMessage Configs are immutable
     */
    public function testSet()
    {
        $config = new ArrayConfig($this->data);
        $config[2] = 'Three';
    }
    /**
     * @expectedException Exception
     * @expectedExceptionMessage Configs are immutable
     */
    public function testUnset()
    {
        $config = new ArrayConfig($this->data);
        unset($config[1]);
    }

    public function testGet()
    {
        $config = new ArrayConfig($this->data);
        $this->assertEquals('One', $config[0]);
        $this->assertEquals('Two', $config[1]);
    }

    public function testExists()
    {
        $config = new ArrayConfig($this->data);
        $this->assertTrue(isset($config[0]));
        $this->assertFalse(isset($config[2]));
    }
}
