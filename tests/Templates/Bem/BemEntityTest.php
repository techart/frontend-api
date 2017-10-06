<?php
namespace Techart\Frontend\Tests\Templates\Bem;

use \Techart\Frontend\Templates\Bem;

class BemEntityTest extends \PHPUnit_Framework_TestCase
{
	/** @var Bem\BemEntity|\PHPUnit_Framework_MockObject_MockObject */
	private $bemEntity;

	protected function setUp()
	{
		parent::setUp();
		$this->bemEntity = $this
			->getMockBuilder(Bem\BemEntity::class)
			->getMockForAbstractClass();
	}

	public function testSetName()
	{
		$this->bemEntity->setName('test');

		$reflection = new \ReflectionClass(Bem\BemEntity::class);
		$nameProperty = $reflection->getProperty('name');
		$nameProperty->setAccessible(true);

		$this->assertEquals('test', $nameProperty->getValue($this->bemEntity));
	}

	public function providerAddMod()
	{
		return [
			[
				[
					['mod', 'value'],
					['mod2', 'value2'],
					['mod3', 'value3']
				],
				[
					'mod' => 'value',
					'mod2' => 'value2',
					'mod3' => 'value3'
				]
			],
			[
				[
					['mod_value', null],
					['mod2', null],
					['', null],
				],
				[
					'mod' => 'value',
					'mod2' => null,
				]
			]
		];
	}

	/**
	 * @dataProvider providerAddMod
	 * @param array $result
	 * @param array $modsForAdd
	 */
	public function testAddMod($modsForAdd, $result)
	{
		foreach ($modsForAdd as $modForAdd) {
			$this->bemEntity->addMod($modForAdd[0], $modForAdd[1]);
		}
		$reflection = new \ReflectionClass(Bem\BemEntity::class);
		$nameProperty = $reflection->getProperty('mods');
		$nameProperty->setAccessible(true);

		$this->assertEquals($result, $nameProperty->getValue($this->bemEntity));
	}

	public function providerRemoveMod()
	{
		return [
			[
				[
					'mod' => 'value',
					'mod2' => 'value2',
					'mod3' => 'value3'
				],
				[
					'mod2', 'mod5'
				],
				[
					'mod' => 'value',
					'mod3' => 'value3'
				],

			],
			[
				[
					'mod' => 'value',
					'mod2' => null,
				],
				[
					'mod2', 'mod2', 'mod', 'mod5'
				],
				[]
			]
		];
	}

	/**
	 * @dataProvider providerRemoveMod
	 * @param array $mods
	 * @param array $modsForRemove
	 * @param array $result
	 */
	public function testRemoveMod($mods, $modsForRemove, $result)
	{
		$reflection = new \ReflectionClass(Bem\BemEntity::class);
		$nameProperty = $reflection->getProperty('mods');
		$nameProperty->setAccessible(true);
		$nameProperty->setValue($this->bemEntity, $mods);

		foreach ($modsForRemove as $modForRemove) {
			$this->bemEntity->removeMod($modForRemove);
		}

		$this->assertEquals($result, $nameProperty->getValue($this->bemEntity));
	}

	public function providerClearMods()
	{
		return [
			[
				[
					'mod' => 'value',
					'mod2' => 'value2',
					'mod3' => 'value3'
				]

			],
			[
				[
					'mod' => 'value',
					'mod2' => null,
				]
			]
		];
	}

	/**
	 * @dataProvider providerRemoveMod
	 * @param array $mods
	 */
	public function testClearMods($mods)
	{
		$reflection = new \ReflectionClass(Bem\BemEntity::class);
		$nameProperty = $reflection->getProperty('mods');
		$nameProperty->setAccessible(true);
		$nameProperty->setValue($this->bemEntity, $mods);

		$this->bemEntity->clearMods();

		$this->assertEquals([], $nameProperty->getValue($this->bemEntity));
	}

	public function providerToString()
	{
		return [
			[
				[
					'mod' => 'value',
					'mod2' => 'value2',
					'mod3' => 'value3'
				],
				'dummy-element',
				'dummy-element dummy-element--mod_value dummy-element--mod2_value2 dummy-element--mod3_value3'
			],
			[
				[
					'mod' => 'value',
					'mod2' => null,
				],
				'dummy-element',
				'dummy-element dummy-element--mod_value dummy-element--mod2'
			]
		];
	}

	/**
	 * @dataProvider providerToString
	 * @param $mods
	 * @param $bemName
	 * @param $result
	 */
	public function testToString($mods, $bemName, $result)
	{
		$reflection = new \ReflectionClass(Bem\BemEntity::class);
		$nameProperty = $reflection->getProperty('mods');
		$nameProperty->setAccessible(true);
		$nameProperty->setValue($this->bemEntity, $mods);

		$this->bemEntity
			->method('getBemName')
			->willReturn($bemName);

		$this->assertEquals($result, $this->bemEntity->toString());
	}
}