<?php

namespace Techart\Frontend\Tests\Templates;

use \Techart\Frontend\Templates\StringBuilder;


class StringBuilderTest extends \PHPUnit_Framework_TestCase
{
	private $builder;

	protected function setUp()
	{
		parent::setUp();
		$this->builder = new StringBuilder('b-main');
	}
	public function testNames()
	{
		$this->assertEquals('b-main', $this->builder->getString());
		$this->assertEquals('b-main', $this->builder->getStringAndReset());

		$this->builder->setName('b-news');
		$this->assertEquals('b-news', $this->builder->getString());
		$this->assertEquals('b-news', $this->builder->getStringAndReset());
	}

	public function testElements()
	{
		$this->builder->setElem('title');
		$this->assertEquals('b-main__title', $this->builder->getString());
		$this->assertEquals('b-main__title', $this->builder->getStringAndReset());
	}

	public function testModificators()
	{
		$this->builder->setMod('mod');
		$this->assertEquals('b-main b-main--mod', $this->builder->getString());
		$this->assertEquals('b-main b-main--mod', $this->builder->getStringAndReset());

		$this->builder->setMod('mod');
		$this->builder->setValue('value');
		$this->assertEquals('b-main b-main--mod_value', $this->builder->getString());
		$this->assertEquals('b-main b-main--mod_value', $this->builder->getStringAndReset());

		$this->builder->setElem('title');
		$this->assertEquals('b-main__title', $this->builder->getString());
		$this->assertEquals('b-main__title', $this->builder->getStringAndReset());

		$this->builder->setElem('title');
		$this->builder->setMod('mod');
		$this->assertEquals('b-main__title b-main__title--mod', $this->builder->getString());
		$this->assertEquals('b-main__title b-main__title--mod', $this->builder->getStringAndReset());

		$this->builder->setElem('title');
		$this->builder->setMod('mod');
		$this->builder->setValue('value');
		$this->assertEquals('b-main__title b-main__title--mod_value', $this->builder->getString());
		$this->assertEquals('b-main__title b-main__title--mod_value', $this->builder->getStringAndReset());
	}
}