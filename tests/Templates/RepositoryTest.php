<?php

namespace Techart\Frontend\Tests\Templates;

use Techart\Frontend\Templates\Repository;


class RepositoryTest extends \PHPUnit_Framework_TestCase
{
	private $repository;

	protected function setUp() {
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
		$this->repository = new Repository($factory);
	}

	public function testCachePath()
	{
		$this->assertInternalType('string', $this->repository->cachePath());
	}

	public function testAddRenderer()
	{
		$this->repository->add('newMode', 'ClassName');
		return array('newMode', $this->repository);
	}

	/**
	 * @depends testAddRenderer
	 */
	public function testGetAddedRenderer()
	{
		$params = func_get_args()[0];
		$this->assertTrue($params[1]->get($params[0]));
	}
}