<?php

namespace Techart\Frontend\Tests\Templates;

use Techart\Frontend\Templates\Manager;


class ManagerTest extends \PHPUnit_Framework_TestCase
{
	private $manager;

	protected function setUp()
	{
		parent::setUp();
		$env = $this->getMockBuilder('Techart\Frontend\EnvironmentInterface')
			->setMethods(['getName', 'isProd', 'isDev', 'isHot'])
			->getMock();
		$env->method('getName')->willReturn('prod');

		$factory = $this->getMockBuilder('Techart\Frontend\Templates\Factory')
			->setConstructorArgs([$env, '', ''])
			->setMethods(['createRenderer'])
			->getMock();

		$factory->method('createRenderer')->willReturn(true);

		$repository = $this->getMockBuilder('Techart\Frontend\Templates\Repository')
			->setConstructorArgs([$factory])
			->setMethods(['add', 'get', 'cachePath'])
			->getMock();

		$renderer = $this->getMockBuilder('Techart\Frontend\Templates\Renderer')
				->setConstructorArgs(['', $env, null, null])
				->setMethods(['render', 'renderBlock'])
				->getMock();

		$renderer->expects($this->any())
			->method('render')
			->will($this->returnValue('string'));
		$renderer->expects($this->any())
				->method('renderBlock')
				->will($this->returnValue('string'));

		$repository->expects($this->any())
			->method('get')
			->withConsecutive(
				[$this->equalTo('default')],
				[$this->equalTo('raw')],
				[$this->equalTo('custom')]
			)->willReturn($renderer);

		$repository->expects($this->any())
				->method('add')
				->with($this->equalTo('custom'), $this->equalTo('class_name'));

		$this->manager = new Manager($repository);
	}

	public function testAddRenderer()
	{
		$this->manager->addRenderer('custom', 'class_name');
	}

	public function testModeRender()
	{
		$this->manager->render('tpl_name', []);
		$this->manager->render('tpl_name', [], 'raw');
		$this->manager->render('tpl_name', [], 'custom');
	}
}
