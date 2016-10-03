<?php

namespace Techart\Frontend;

use Techart\Frontend\Assets\Manager;
use Techart\Frontend\Templates\Repository;
use Techart\Frontend\Templates\Factory;
use Techart\Frontend\Templates\Manager as TemplatesManager;


class Frontend
{
	private $assetsManager;
	private $templatesManager;
	private $env;
	private $pathResolver;

	public function __construct(EnvironmentInterface $env, PathResolver $pathResolver)
	{
		$this->env = $env;
		$this->pathResolver = $pathResolver;
	}

	public function assets()
	{
		return $this->assetsManager ?: $this->assetsManager = new Manager($this->env, $this->pathResolver);
	}

	public function templates()
	{
		return $this->templatesManager ?: $this->templatesManager = new TemplatesManager(
			new Repository(
				new Factory($this->env, $this->pathResolver)
			)
		);
	}

	public function closure($callback, $params = array())
	{
		return new Closure($callback, $params);
	}
}
