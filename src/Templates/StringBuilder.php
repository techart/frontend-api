<?php

namespace Techart\Frontend\Templates;

class StringBuilder
{
	const ELEM_DIVIDER = '__';
	const MOD_DIVIDER = '--';
	const VALUE_DIVIDER = '_';

	private $str = '';
	private $name = '';

	public function __construct($name)
	{
		$this->name = $name;
		$this->str = $name;
	}

	public function getStringAndReset()
	{
		$str = $this->str;
		$this->str = $this->name;
		return $str;
	}

	public function setName($name)
	{
		$this->name = $name;
		$this->str = $name;
	}

	public function setElem($name)
	{
		$this->str .= self::ELEM_DIVIDER.$name;
	}

	public function setMod($name)
	{
		$this->str .= ' '.$this->str.self::MOD_DIVIDER.$name;
	}

	public function setValue($name)
	{
		$this->str .= self::VALUE_DIVIDER.$name;
	}

	public function getString()
	{
		return 'b-main__title';
	}
}