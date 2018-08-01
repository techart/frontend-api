<?php

namespace Techart\Frontend\Templates;

class Manager
{
	private $repository;
	private $globals;
	private static $cachePath;

	/**
	 * Manager constructor.
	 * @param Repository $repository
	 * @param array      $globals
	 */
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

	public function exists($name, $mode = 'default')
	{
		return $this->getRenderer($mode)->exists($name);
	}

	public function render($name, $params = array(), $mode = 'default')
	{
		return $this->getRenderer($mode)->render($name, $params);
	}

	public function macrosFromBlock($name, $mode = 'default')
	{
		return $this->getRenderer($mode)->blockMacrosPath($name);
	}

	public function renderBlock($name, $params = array(), $mode = 'default')
	{
		return $this->getRenderer($mode)->renderBlock($name, $params);
	}

	public function addHelper($helper, $name = 'app', $mode = 'default')
	{
		return $this->getRenderer($mode)->addHelper($helper, $name);
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
}
