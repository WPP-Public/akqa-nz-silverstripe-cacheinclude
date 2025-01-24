<?php

namespace Heyday\CacheInclude\Tests;

use Heyday\CacheInclude\Configs\ArrayConfig;
use PHPUnit\Framework\TestCase;

class ArrayConfigTest extends TestCase
{
    protected $testData;

    protected function setUp(): void
    {
        $this->testData = [
            'One',
            'Two'
        ];
    }

    protected function tearDown(): void
    {
        $this->testData = null;
    }

    public function testIteration()
    {
        $config = new ArrayConfig($this->testData);
        foreach ($config as $key => $val) {
            $this->assertEquals($this->testData[$key], $val);
        }
    }


    public function testSet()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Configs are immutable');

        $config = new ArrayConfig($this->testData);
        $config[2] = 'Three';
    }


    public function testUnset()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Configs are immutable');

        $config = new ArrayConfig($this->testData);
        unset($config[1]);
    }


    public function testGet()
    {
        $config = new ArrayConfig($this->testData);
        $this->assertEquals('One', $config[0]);
        $this->assertEquals('Two', $config[1]);
    }


    public function testExists()
    {
        $config = new ArrayConfig($this->testData);
        $this->assertTrue(isset($config[0]));
        $this->assertFalse(isset($config[2]));
    }
}
