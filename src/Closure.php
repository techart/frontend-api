<?php

namespace Techart\Frontend;

class Closure
{
	private $callback;
	private $params;

	public function __construct($callback, $params = array())
	{
		$this->callback = $callback;
		$this->params = $params;
	}

	public function __toString()
	{
		return call_user_func_array($this->callback, $this->params);
	}
}
