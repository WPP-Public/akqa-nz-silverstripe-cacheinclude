<?php

namespace Heyday\CacheInclude\Tests;

use Heyday\CacheInclude\KeyCreators\KeyCreator;

class KeyCreatorTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        \Versioned::choose_site_stage();
        \SSViewer::set_theme('theme');
        $this->keyCreator = new KeyCreator;
    }

    protected function tearDown()
    {
        $this->keyCreator = null;
    }

    public function testNoContext()
    {
        $this->assertEquals('theme.Live.test', $this->keyCreator->getKey(
            'test',
            new \Controller,
            array(
                'context' => 'no'
            )
        ));
    }

    public function testPageContext()
    {
        $controller = new \Controller;
        $controller->setURLParams(array(
            'URLSegment' => 'testing'
        ));
        $this->assertEquals('theme.Live.testing.test', $this->keyCreator->getKey(
            'test',
            $controller,
            array(
                'context' => 'page'
            )
        ));
    }

    public function testUrlParamsContext()
    {
        $controller = new \Controller;
        $controller->setURLParams(array(
            'URLSegment' => '1',
            'Action' => '2',
            'ID' => '3'
        ));
        $this->assertEquals('theme.Live.1.2.3.test', $this->keyCreator->getKey(
            'test',
            $controller,
            array(
                'context' => 'url-params'
            )
        ));
    }

    public function testFullPageContext()
    {
        $controller = new TestController;
        $controller->setRequest(new \SS_HTTPRequest('GET', 'test', array(
            'url' => '1/2/3',
            'var1' => 'test',
            'flush' => 1
        )));
        $this->assertEquals('theme.Live.' . md5(http_build_query(array('url' => '1/2/3', 'var1' => 'test'))) . '.test', $this->keyCreator->getKey(
            'test',
            $controller,
            array(
                'context' => 'full'
            )
        ));
    }

    public function testControllerContext()
    {
        $controller = new \Controller;
        $this->assertEquals('theme.Live.Controller.test', $this->keyCreator->getKey(
            'test',
            $controller,
            array(
                'context' => 'controller'
            )
        ));
    }
}

class TestController extends \Controller
{
    public function setRequest($request)
    {
        $this->request = $request;
    }
}
