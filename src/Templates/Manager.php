<?php

namespace Techart\Frontend\Templates;

use Techart\Frontend\Closure;

class Manager
{
	private $repository;
	private $globals;
	private static $cachePath;

	public function __construct($repository, $globals = array())
	{
		$this->repository = $repository;
		$this->globals = $globals;
		self::$cachePath = $repository->cachePath();

		foreach ($repository->getModsList() as $mode) {
			$this->setupRendererGlobals($mode);
		}
	}

	protected function setupRendererGlobals($mode)
	{
		$this->getRenderer($mode)->addGlobal('renderer', $this);
		foreach ($this->globals as $name => $value) {
			$this->getRenderer($mode)->addGlobal($name, $value);
		}
	}

	public function render($name, $params = array(), $mode = 'default')
	{
		return $this->getRenderer($mode)->render($name, $this->processParams($params));
	}

	public function macrosFromBlock($name, $mode = 'default')
	{
		return $this->getRenderer($mode)->blockMacrosPath($name);
	}

	public function renderBlock($name, $params = array(), $mode = 'default')
	{
		return $this->getRenderer($mode)->renderBlock($name, $this->processParams($params));
	}

	public function addRenderer($mode, $name, $params = array())
	{
		$this->repository->add($mode, $name, $params);
		$this->setupRendererGlobals($mode);
	}

	public static function clearCache()
	{
		if (!self::$cachePath) {
			throw new \Exception('No cache path');
		}

		return self::recursiveClear(self::$cachePath, true);
	}

	private static function recursiveClear($path, $isCacheFolder = false)
	{
		$files = array_diff(scandir($path), array('.', '..'));
		foreach ($files as $file) {
			(is_dir("$path/$file")) ? self::recursiveClear("$path/$file") : unlink("$path/$file");
		}
		if (!$isCacheFolder) {
			return rmdir($path);
		}

		return true;
	}

	private function getRenderer($mode)
	{
		return $this->repository->get($mode);
	}

	private function processParams($params)
	{
		return $this->checkClosures($params);
	}

	private function checkClosures($params)
	{
		if (!empty($params)) {
			foreach ($params as $name => $param) {
				if (is_callable($param)) {
					$params[$name] = new Closure($param);
				}
			}
		}

		return $params;
	}
}