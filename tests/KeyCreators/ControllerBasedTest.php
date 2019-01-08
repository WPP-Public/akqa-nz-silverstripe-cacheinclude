<?php

namespace Heyday\CacheInclude\KeyCreators;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\NullHTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\SSViewer;

class ControllerBasedTest extends SapphireTest
{
    /**
     * @var ControllerBased
     */
    protected $keyCreator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $controllerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    protected function setUp()
    {
        $kernel = Injector::inst()->get(Kernel::class);
        $kernel->setEnvironment(Kernel::DEV);
        Config::modify()->set(SSViewer::class, 'themes', ['theme']);

        $this->controllerMock = $this->getMockBuilder(Controller::class)
            ->getMock();
        $this->keyCreator = new ControllerBased($this->controllerMock);
    }

    protected function tearDown()
    {
        $this->keyCreator = null;
    }

    public function testNoContext()
    {
        $request = $this->getMockBuilder(NullHTTPRequest::class)
            ->getMock();
        $request->expects($this->once())
            ->method('isAjax')
            ->will($this->returnValue(false));

        $this->controllerMock->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $this->assertEquals(
            ['dev', md5(json_encode(['theme'])), $request->getScheme(), 'test'],
            $this->keyCreator->getKey(
                'test',
                ['context' => 'no']
            )
        );
    }

    public function testPageContext()
    {
        $request = $this->getMockBuilder(NullHTTPRequest::class)
            ->getMock();
        $request->expects($this->once())
            ->method('getURL')
            ->will($this->returnValue('testurl?test=hello'));

        $this->controllerMock->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $this->assertEquals(
            ['dev', md5(json_encode(['theme'])), $request->getScheme(), md5('testurl?test=hello'), 'test'],
            $this->keyCreator->getKey(
                'test',
                ['context' => 'page']
            )
        );
    }

    public function testFullContext()
    {
        $request = $this->getMockBuilder(NullHTTPRequest::class)
            ->getMock();
        $request->expects($this->once())
            ->method('getURL')
            ->will($this->returnValue('testurl'));

        $this->controllerMock->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $this->assertEquals(
            ['dev', md5(json_encode(['theme'])), $request->getScheme(), md5('testurl'), 'test'],
            $this->keyCreator->getKey(
                'test',
                ['context' => 'full']
            )
        );
    }
}
