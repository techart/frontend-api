<?php

namespace Techart\Frontend\Templates;

class SourceMap
{
	private $sourceMap = array();
	private $src = '';
	private $cacheFile = '';
	private $cachePath = '';
	private static $instance;

	private function __construct($src, $cache_path)
	{
		$this->src = $src;
		$this->cacheFile = $cache_path . '/source_map.cache.php';
		$this->cachePath = $cache_path;
		$this->sourceMap = $this->getSourceMap();
	}

	public static function getInstance($src, $cachePath)
	{
		if (self::$instance === null) {
			self::$instance = new self($src, $cachePath);
		}
		return self::$instance;
	}

	public function find($name)
	{
		if (array_key_exists($name, $this->sourceMap)) {
			return key($this->sourceMap[$name]);
		}
		return '';
	}

	public function templatePath($name)
	{
		foreach ($this->sourceMap as $map) {
			if (!isset($map[$name])) continue;

			return $map[$name];
		}

		return '';
	}

	public function add($path, $name)
	{
		$this->sourceMap = array_merge($this->sourceMap, array(
				$name => array($path => realpath($this->src . $path)),
			));
		$this->checkCacheDir();

		$cache = "<?php return " . var_export($this->sourceMap, true) . ";";
		file_put_contents($this->cacheFile, $cache);
	}

	private function getSourceMap()
	{
		if (is_file($this->cacheFile) && ($inc = include($this->cacheFile)) && is_array($inc)) {
			return $inc;
		}
		return array();
	}

	private function checkCacheDir()
	{
		if (!is_dir($this->cachePath)) {
			$old_mask = umask(0);
			mkdir($this->cachePath, 0777, true);
			if (!is_file($this->cacheFile)) {
				file_put_contents($this->cacheFile, '');
			}
			umask($old_mask);
		}
	}
}
