<?php

namespace Techart\Frontend\Templates\Bem;

class Block extends BemEntity
{
	const PREFIX_DIVIDER = '-';
	
	/** @var  string префикс блока */
	protected $prefix;

	/**
	 * Block constructor.
	 * @param string $name - имя блока
	 * @param string $prefix - префикс блока
	 */
	public function __construct($name, $prefix = 'b')
	{
		$this->setPrefix($prefix);
		$this->setName($name);
	}
	
	public function setPrefix($prefix)
	{
		$this->prefix = $prefix;
	}

	/**
	 * @return string
	 */
	public function getBemName()
	{
		return $this->prefix . self::PREFIX_DIVIDER . $this->name;
	}

	/**
	 * Возврашает новый элемент текущего блока
	 * @param string $name
	 * @return Element
	 */
	public function elem($name)
	{
		return new Element($name, $this);
	}

}
