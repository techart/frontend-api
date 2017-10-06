<?php

namespace Techart\Frontend\Templates;

use Techart\Frontend\EnvironmentInterface;

class Factory
{
	private $config = array(
		'dev' => array(
			'auto_reload' => true,
			'debug' => true,
		),
		'prod' => array(
			'auto_reload' => true,
		),
		'hot' => array(
			'auto_reload' => true,
			'debug' => true,
		),
	);

	private $env;
	private $pathResolver;

	public function __construct(EnvironmentInterface $env, $pathResolver)
	{
		$this->env = $env;
		$this->pathResolver = $pathResolver;
		$this->config['prod']['cache'] = new Cache($this->cachePath());
	}

	public function cachePath()
	{
		return $this->pathResolver->twigCachePath();
	}

	public function createRenderer($name, $config = array())
	{
		if (!class_exists($name)) {
			throw new \Exception('class not exist');
		}

		return new $name($this->pathResolver->frontendPath(),
			$this->env,
			new Loader(
				$this->pathResolver->frontendPath(),
				SourceMap::getInstance(
					$this->pathResolver->frontendPath(),
					$this->cachePath()
				),
				$this->env
			),
			SourceMap::getInstance(
				$this->pathResolver->frontendPath(),
				$this->cachePath()
			),
			array_merge($this->envConfig(), $config));

	}

	private function envConfig()
	{
		return $this->config[$this->env->getName()];
	}
}
