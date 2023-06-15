<?php

namespace Techart\Frontend\Templates;

use Techart\Frontend\EnvironmentInterface;
use Twig\Error\LoaderError;

class Loader extends \Twig\Loader\FilesystemLoader
{
	protected $sourceMap = null;
	protected $env = '';

	public function __construct($paths, $sourceMap, EnvironmentInterface $env)
	{
		$this->env = $env;
		$this->sourceMap = $sourceMap;
		parent::__construct($paths);
	}

	public function addPath(string $path, string $namespace = self::MAIN_NAMESPACE):void
	{
		// invalidate the cache
		$this->cache = $this->errorCache = array();

		if ($this->env->isProd() && !is_dir($path)) {
			throw new LoaderError(sprintf('The "%s" directory does not exist.', $path));
		}

		$this->paths[$namespace][] = rtrim($path, '/\\');
	}

	/**
	 * @param string $name
	 * @param int    $time
	 * @return bool
	 * @throws LoaderError
	 *
	 * @ тут нужна. мы считаем что если исходника вдруг нет, то кеш актуален.
	 *
	 */
	public function isFresh(string $name, int $time):bool
	{
		return (int)@filemtime($this->findTemplate($name)) <= $time;
	}

	protected function findTemplate(string $name, bool $throw = true)
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

			throw new LoaderError($this->errorCache[$name]);
		}

		$this->validateName($name);

		list($namespace, $shortname) = $this->parseName($name);

		if (!isset($this->paths[$namespace])) {
			$this->errorCache[$name] = sprintf('There are no registered paths for namespace "%s".', $namespace);

			if (!$throw) {
				return false;
			}

			throw new LoaderError($this->errorCache[$name]);
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
		throw new LoaderError($this->errorCache[$name]);
	}

	private function normalizeName(string $name): string
	{
		return preg_replace('#/{2,}#', '/', str_replace('\\', '/', $name));
	}

	private function parseName(string $name, string $default = self::MAIN_NAMESPACE): array
	{
		if (isset($name[0]) && '@' == $name[0]) {
			if (false === $pos = strpos($name, '/')) {
				throw new LoaderError(sprintf('Malformed namespaced template name "%s" (expecting "@namespace/template_name").', $name));
			}

			$namespace = substr($name, 1, $pos - 1);
			$shortname = substr($name, $pos + 1);

			return [$namespace, $shortname];
		}

		return [$default, $name];
	}

	private function validateName(string $name): void
	{
		if (false !== strpos($name, "\0")) {
			throw new LoaderError('A template name cannot contain NUL bytes.');
		}

		$name = ltrim($name, '/');
		$parts = explode('/', $name);
		$level = 0;
		foreach ($parts as $part) {
			if ('..' === $part) {
				--$level;
			} elseif ('.' !== $part) {
				++$level;
			}

			if ($level < 0) {
				throw new LoaderError(sprintf('Looks like you try to load a template outside configured directories (%s).', $name));
			}
		}
	}

}
