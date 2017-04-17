<?php

namespace Techart\Frontend\Templates;

class Block
{
	private $name;
	private $builder = null;

	public function __construct($name)
	{
		$this->name = $name;
	}

	public function setName($name)
	{
		if ($name) {
			$this->name = $name;
			$this->stringBuilder()->setName("b-$name");
		}
	}

	public function cls()
	{
		return $this;
	}

	public function elem($name)
	{
		$this->stringBuilder()->setElem($name);
		return $this;
	}

	public function mod($name)
	{
		$this->stringBuilder()->setMod($name);
		return $this;
	}

	public function val($name)
	{
		$this->stringBuilder()->setValue($name);
		return $this;
	}

	protected function stringBuilder()
	{
		return $this->builder ?: $this->builder = new StringBuilder("b-$this->name");
	}

	public function __toString()
	{
		return $this->stringBuilder()->getStringAndReset();
	}
}
