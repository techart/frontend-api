<?php

namespace Techart\Frontend\Templates\Bem;

class Element extends BemEntity
{
	const ELEM_DIVIDER = '__';
	
	/** @var  Block родительский блок */
	protected $block;

	/**
	 * Element constructor.
	 * @param string $name
	 * @param Block $block
	 */
	public function __construct($name, $block)
	{
		$this->name = $name;
		$this->block = $block;
	}

	/**
	 * @return string
	 */
	public function getBemName()
	{
		return $this->block()->getBemName() . self::ELEM_DIVIDER . $this->name;
	}

	/**
	 * @return Block
	 */
	public function block()
	{
		return $this->block;
	}
}
