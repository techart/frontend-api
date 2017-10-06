<?php
namespace Techart\Frontend\Tests\Templates\Bem;

use \Techart\Frontend\Templates\Bem;

class BlockTest extends \PHPUnit_Framework_TestCase
{
	/** @var Bem\Block|\PHPUnit_Framework_MockObject_MockObject */
	private $block;

	protected function setUp()
	{
		parent::setUp();
		$this->block = $this->getMockBuilder(Bem\Block::class)
			->setMethods(null)
			->setConstructorArgs(array('dummy-block', 'b'))
			->getMock();
	}

	public function testSetPrefix()
	{
		$this->block->setPrefix('l');
		$reflection = new \ReflectionClass(Bem\Block::class);
		$nameProperty = $reflection->getProperty('prefix');
		$nameProperty->setAccessible(true);
		$this->assertEquals('l', $nameProperty->getValue($this->block));
	}

	public function testGetBemName()
	{
		$this->block->setName('dummy-block');
		$this->block->setPrefix('b');
		$this->assertEquals('b-dummy-block', $this->block->getBemName());
	}

	public function testElementClassExists()
	{
		$this->assertTrue(class_exists(Bem\Element::class));

		return Bem\Element::class;
	}

	/**
	 * @depends      testElementClassExists
	 * @param string $elementClass
	 */
	public function testCreateElement($elementClass)
	{
		$this->assertInstanceOf($elementClass, $this->block->elem('dummy-elem'));
	}
}