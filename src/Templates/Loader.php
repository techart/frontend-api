<?php

namespace Techart\Frontend\Templates;

use Techart\Frontend\EnvironmentInterface;

class Loader extends \Twig_Loader_Filesystem
{
	protected $sourceMap = null;
	protected $env = '';

	public function __construct($paths, $sourceMap, EnvironmentInterface $env)
	{
		$this->env = $env;
		$this->sourceMap = $sourceMap;
		parent::__construct($paths);
	}

	public function addPath($path, $namespace = self::MAIN_NAMESPACE)
	{
		// invalidate the cache
		$this->cache = $this->errorCache = array();

		if ($this->env->isProd() && !is_dir($path)) {
			throw new \Twig_Error_Loader(sprintf('The "%s" directory does not exist.', $path));
		}

		$this->paths[$namespace][] = rtrim($path, '/\\');
	}

	/**
	 * @param string $name
	 * @param int    $time
	 * @return bool
	 * @throws \Twig_Error_Loader
	 *
	 * @ тут нужна. мы считаем что если исходника вдруг нет, то кеш актуален.
	 *
	 */
	public function isFresh($name, $time)
	{
		return (int)@filemtime($this->findTemplate($name)) <= $time;
	}

	protected function findTemplate($name)
	{
		$throw = func_num_args() > 1 ? func_get_arg(1) : true;
		$name = $this->normalizeName($name);

		if (isset($this->cache[$name])) {
			return $this->cache[$name];
		}

		if (isset($this->errorCache[$name])) {
			if (!$throw) {
				return false;
			}

			throw new \Twig_Error_Loader($this->errorCache[$name]);
		}

		$this->validateName($name);

		list($namespace, $shortname) = $this->parseName($name);

		if (!isset($this->paths[$namespace])) {
			$this->errorCache[$name] = sprintf('There are no registered paths for namespace "%s".', $namespace);

			if (!$throw) {
				return false;
			}

			throw new \Twig_Error_Loader($this->errorCache[$name]);
		}


		foreach ($this->paths[$namespace] as $path) {
			if ($this->env->isProd() && $templateCachedPath = $this->sourceMap->templatePath($shortname)) {
				return $this->cache[$name] = $templateCachedPath;
			}
			if (is_file($path . '/' . $shortname)) {
				if (false !== $realpath = realpath($path . '/' . $shortname)) {
					return $this->cache[$name] = $realpath;
				}

				return $this->cache[$name] = $path . '/' . $shortname;
			}
		}

		$this->errorCache[$name] = sprintf('Unable to find template "%s" (looked into: %s).', $name, implode(', ', $this->paths[$namespace]));

		if (!$throw) {
			return false;
		}
		throw new \Twig_Error_Loader($this->errorCache[$name]);
	}
}
