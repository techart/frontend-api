<?php

namespace Techart\Frontend\Templates;

class Repository
{
	private $renders = array();
	private $factory;

	public function __construct($factory)
	{
		$this->factory = $factory;
		$this->createDefaultRenders();
	}

	public function add($mode, $name, $params = array())
	{
		$this->renders[$mode] = $this->factory->createRenderer($name, $params);
	}

	public function get($mode)
	{
		return $this->renders[$mode];
	}

	public function cachePath()
	{
		return $this->factory->cachePath;
	}

	private function createDefaultRenders()
	{
		$this->add('default', '\Techart\Frontend\Templates\Renderer');
		$this->add('raw', '\Techart\Frontend\Templates\Renderer', array('autoescape' => false));
	}


}
