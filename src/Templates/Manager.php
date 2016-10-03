<?php

namespace Techart\Frontend\Templates;

use Techart\Frontend\Closure;

class Manager
{
	private $repository;
	private static $cachePath;

	public function __construct($repository)
	{
		$this->repository = $repository;
		self::$cachePath = $repository->cachePath();
	}

	public function render($name, $params = array(), $mode = 'default')
	{
		return $this->getRenderer($mode)->render($name, $this->processParams($params));
	}

	public function renderBlock($name, $params = array(), $mode = 'default')
	{
		return $this->getRenderer($mode)->renderBlock($name, $this->processParams($params));
	}

	public function addRenderer($mode, $name, $params = array())
	{
		$this->repository->add($mode, $name, $params);
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
		return array_merge($this->checkClosures($params), array('renderer' => $this));
	}

	private function checkClosures($params)
	{
		if (!empty($params)){
			foreach ($params as $name => $param) {
				if (is_callable($param)) {
					$params[$name] = new Closure($param);
				}
			}
		}

		return $params;
	}
}