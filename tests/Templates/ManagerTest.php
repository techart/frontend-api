<?php

namespace Techart\Frontend\Tests\Templates;

use Techart\Frontend\Templates\Manager;
use Techart\Frontend\PathResolver;


class ManagerTest extends \PHPUnit_Framework_TestCase
{
    private $manager;
    private $env;
    private $factory;
    private $loader;
    private $repository;
    private $renderer;

    protected function setUp()
    {
        parent::setUp();
        $this->createEnv();
        $this->createFactory();
        $this->createLoader();
        $this->createRepository();
        $this->createRenderer();

        $this->manager = new Manager($this->repository);
    }

    public function testAddRenderer()
    {
        $this->repository->expects($this->any())
            ->method('add')
            ->with($this->equalTo('custom'), $this->equalTo('class_name'));

        $this->repository->expects($this->any())
            ->method('get')
            ->willReturn($this->renderer);

        $this->manager->addRenderer('custom', 'class_name');
    }

    /**
     * @depends testAddRenderer
     */
    public function testModeRender()
    {
        $this->repository->expects($this->any())
            ->method('get')
            ->withConsecutive(
                [$this->equalTo('default')],
                [$this->equalTo('raw')],
                [$this->equalTo('custom')]
            )->willReturn($this->renderer);
        $this->manager->render('tpl_name', []);
        $this->manager->render('tpl_name', [], 'raw');
        $this->manager->render('tpl_name', [], 'custom');
    }

    private function createEnv()
    {
        $this->env = $this->getMockBuilder('Techart\Frontend\EnvironmentInterface')
            ->setMethods(['getName', 'isProd', 'isDev', 'isHot', 'switchTo'])
            ->getMock();
        $this->env->method('getName')->willReturn('prod');
    }

    private function createFactory()
    {
        $this->factory = $this->getMockBuilder('Techart\Frontend\Templates\Factory')
            ->setConstructorArgs([$this->env, new PathResolver('/some/path/')])
            ->setMethods(['createRenderer'])
            ->getMock();

        $this->factory->method('createRenderer')->willReturn(true);
    }

    private function createLoader()
    {
        $this->loader = $this->getMockBuilder('Techart\Frontend\Templates\Loader')
            ->setMethods(['addPath'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function createRepository()
    {
        $this->repository = $this->getMockBuilder('Techart\Frontend\Templates\Repository')
            ->setConstructorArgs([$this->factory])
            ->setMethods(['add', 'get', 'cachePath'])
            ->getMock();
    }

    private function createRenderer()
    {
        $this->renderer = $this->getMockBuilder('Techart\Frontend\Templates\Renderer')
            ->setConstructorArgs(['', $this->env, $this->loader, null])
            ->setMethods(['render', 'renderBlock'])
            ->getMock();

        $this->renderer->expects($this->any())
            ->method('render')
            ->will($this->returnValue('string'));
        $this->renderer->expects($this->any())
            ->method('renderBlock')
            ->will($this->returnValue('string'));
    }
}
