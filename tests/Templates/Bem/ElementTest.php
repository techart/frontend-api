<?php
namespace Techart\Frontend\Tests\Templates\Bem;

use \Techart\Frontend\Templates\Bem;

class ElementTest extends \PHPUnit_Framework_TestCase
{
	/** @var Bem\Element|\PHPUnit_Framework_MockObject_MockObject */
	private $element;

	protected function setUp()
	{
		parent::setUp();
		$block = $this->getMockBuilder(Bem\Block::class)
			->setMethods(['getBemName'])
			->setConstructorArgs(array('dummy-block', 'b'))
			->getMock();
		$block->method('getBemName')->willReturn('b-dummy-block');

		$this->element = $this->getMockBuilder(Bem\Element::class)
			->setMethods(['__toString'])
			->setConstructorArgs(array('dummy-element', $block))
			->getMock();
	}

	public function testGetBemName()
	{
		$this->assertEquals('b-dummy-block__dummy-element', $this->element->getBemName());
	}

	public function testBlock()
	{
		$this->assertInstanceOf(Bem\Block::class, $this->element->block());
	}
}