<?php

namespace Heyday\CacheInclude\Tests;

use Heyday\CacheInclude\Processors\ViewableDataProcessor;

class ViewableDataProcessorTest extends \PHPUnit_Framework_TestCase
{
    protected $processor;

    protected function setUp()
    {
        $this->processor = new ViewableDataProcessor;
    }

    protected function tearDown()
    {
        $this->processor = null;
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testException()
    {
        $processor = $this->processor;
        $processor('test');
    }

    public function testHasMethod()
    {
        $processor = $this->processor;
        $processor->setContext(new TestObject);
        $this->assertEquals('hello', $processor('hello'));
    }

    public function testRenderWith()
    {
        $processor = $this->processor;
        $processor->setContext(new TestObject);
        $this->assertEquals('testing', $processor('testing'));
    }
}

class TestObject extends \ViewableData
{
    public function hello()
    {
        return 'hello';
    }

    public function renderWith($template, $customFields = null)
    {
        return $template;
    }
}
