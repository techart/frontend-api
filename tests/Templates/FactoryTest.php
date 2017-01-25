<?php

namespace Techart\Frontend\Tests\Templates;

use Techart\Frontend\Templates\RendererInterface;
use Techart\Frontend\Templates\Factory;
use Techart\Frontend\PathResolver;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    private $factory;

    protected function setUp()
    {
        parent::setUp();
        $env = $this->getMockBuilder('Techart\Frontend\EnvironmentInterface')
            ->setMethods(['getName', 'isProd', 'isDev', 'isHot', 'switchTo'])
            ->getMock();
        $env->method('getName')->willReturn('prod');
        $this->factory = new Factory($env, new PathResolver('/some/path/'));
    }

    public function providerExistClasses()
    {
        return array(
            array('\Techart\Frontend\Templates\Renderer', []),
            array('\Techart\Frontend\Templates\Renderer', ['param' => 1]),
        );
    }

    public function providerNotExistClasses()
    {
        return array(
            array('SomeRandomName', []),
            array('SomeRandomName', ['param' => 1]),
        );
    }

    /**
     * @dataProvider providerExistClasses
     */
    public function testClassExists($name)
    {
        $this->assertTrue(class_exists($name));
    }

    /**
     * @depends      testClassExists
     * @dataProvider providerExistClasses
     */
    public function testCreateRenderer()
    {
        $parms = func_get_args();
        $this->assertInstanceOf(RendererInterface::class, $this->factory->createRenderer($parms[0], $parms[1]));
    }

    /**
     * @dataProvider providerNotExistClasses
     */
    public function testNotExistClass($name)
    {
        $this->assertFalse(class_exists($name));
    }

    /**
     * @depends      testNotExistClass
     * @dataProvider providerNotExistClasses
     * @expectedException Exception
     */
    public function testNotExistClassExeption()
    {
        $parms = func_get_args();
        $this->factory->createRenderer($parms[0], $parms[1]);
    }
}