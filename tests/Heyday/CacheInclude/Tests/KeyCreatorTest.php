<?php

namespace Heyday\CacheInclude\Tests;

use Heyday\CacheInclude\KeyCreators\KeyCreator;

class KeyCreatorTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        \Versioned::choose_site_stage();
        \SSViewer::set_theme('test');
        $this->keyCreator = new KeyCreator;
    }

    protected function tearDown()
    {
        $this->keyCreator = null;
    }

    public function testNoContext()
    {
        $this->assertEquals('test.Live.test', $this->keyCreator->getKey(
            'test',
            new \Controller,
            array(
                'context' => 'no'
            )
        ));
        $this->assertEquals('test.Live.test', $this->keyCreator->getKey(
            'test',
            new \Controller,
            array(
                'context' => 0
            )
        ));
    }

    public function testPageContext()
    {
        $controller = new \Controller;
        
    }
}