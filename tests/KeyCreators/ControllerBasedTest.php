<?php

namespace Heyday\CacheInclude\KeyCreators;

class ControllerBasedTest extends \PHPUnit_Framework_TestCase
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
        $config = \Config::inst();
        $config->update('Director', 'environment_type', 'dev');
        $config->update('SSViewer', 'theme', 'theme');
        \Versioned::choose_site_stage();
        
        $this->controllerMock = $this->getMock('Controller');
        
        $this->keyCreator = new ControllerBased($this->controllerMock);
    }

    protected function tearDown()
    {
        $this->keyCreator = null;
    }

    public function testNoContext()
    {
        $this->assertEquals(
            array(
                'dev', 'Live', 'theme', 'test'
            ),
            $this->keyCreator->getKey(
                'test',
                array(
                    'context' => 'no'
                )
            )
        );
    }

    public function testPageContext()
    {
        $request = $this->getMock('SS_HTTPRequest', array(), array(), '', false);
        $request->expects($this->once())
            ->method('getURL')
            ->will($this->returnValue('testurl?test=hello'));

        $this->controllerMock->expects($this->once())
            ->method('getRequest')
            ->will(
                $this->returnValue($request)
            );

        $this->assertEquals(
            array(
                'dev',
                'Live',
                'theme',
                md5('testurl?test=hello'),
                'test'
            ),
            $this->keyCreator->getKey(
                'test',
                array(
                    'context' => 'page'
                )
            )
        );
    }

    public function testFullContext()
    {
        $request = $this->getMock('SS_HTTPRequest', array(), array(), '', false);
        $request->expects($this->once())
            ->method('getURL')
            ->will($this->returnValue('testurl'));

        $this->controllerMock->expects($this->once())
            ->method('getRequest')
            ->will(
                $this->returnValue($request)
            );

        $this->assertEquals(
            array(
                'dev',
                'Live',
                'theme',
                md5('testurl'),
                'test'
            ),
            $this->keyCreator->getKey(
                'test',
                array(
                    'context' => 'full'
                )
            )
        );
    }
}
