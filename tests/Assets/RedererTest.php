<?php
namespace Techart\Frontend\Tests\Assets;

use \Techart\Frontend\Assets\Renderer;

class RendererTest extends \PHPUnit_Framework_TestCase
{
	private $renderer;

	protected function setUp()
	{
		parent::setUp();
		$this->renderer = new Renderer();
	}

	public function testCorrectUrlRendered()
	{
		$url = 'http://ya.ru/';
		$this->assertTrue(strpos($this->renderer->tag('css', $url), $url) !== false);
	}

	public function testCorrectTagRendered()
	{
		$this->assertTrue(strpos($this->renderer->tag('css', 'http://ya.ru/'), '<link') === 0);
		$this->assertTrue(strpos($this->renderer->tag('js', 'http://ya.ru/'), '<script') === 0);
	}

	public function testEmptyStringOnInvalidType()
	{
		$this->assertEquals($this->renderer->tag('some_unexisted_type', '/'), '');
	}

	public function testExtraAttrsUsed()
	{
		$this->assertTrue(strpos($this->renderer->tag('css', 'http://ya.ru/', array(
			'test_attr' => 'test_value',
		)), 'test_attr="test_value"') !== false);
	}
}