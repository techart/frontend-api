<?php

namespace Techart\Frontend\Templates;

use Techart\Frontend\EnvironmentInterface;
use Techart\Frontend\Templates\Bem\Block;

class Renderer implements RendererInterface
{
	protected $src;
	protected $twig = null;
	protected $sourceMap = null;
	protected $env = null;
	protected $helpers = array();
	protected $blade = null;

	public function __construct($src, EnvironmentInterface $env, $loader, $sourceMap, $config = array())
	{
		$this->src = $src;
		$this->env = $env;
		$this->sourceMap = $sourceMap;
		$loader->addPath(__DIR__ . '/../../views', 'api');
		$loader->addPath($src . '/src/block', 'block');
		//todo: используется принцип DI, но Twig_Environment подключается прямо в конструуторе :(
		$this->twig = new \Twig_Environment($loader, $config);
		if (isset($config['debug'])) {
			$this->twig->addExtension(new \Twig_Extension_Debug());
		}
	}
	
	public function blade()
	{
		if (!$this->blade) {
			$this->blade = new Blade(
				rtrim($this->src, '/').'/src',
				rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/local/cache/blade'
			);
		}
		return $this->blade;
	}

	public function addGlobal($name, $value)
	{
		$this->twig->addGlobal($name, $value);
		$this->blade()->addGlobal($name, $value);
	}

	public function render($name, $params = array())
	{
		$path = $this->find($name);
		$params = $this->defaultParams($path, $params);
		
		if (preg_match('{^blade:(.+)$}', $path, $m)) {
			$path = $m[1];
			return $this->blade()->render($path, $params);
		}
		

		return $this->twig->render($path, $params);
	}

	public function blockMacrosPath($name)
	{
		$parts = explode('/', $name);
		return "@block/$name/" . end($parts) . '.macros.twig';
	}

	protected function defaultParams($path, $params)
	{
		$params['__DIR__'] = $this->dirnameFor($path);
		//TODO: block сейчас вбит гвоздями, рассмотреть возможность подключения через addHelper
		$params['block'] = new Block(!empty($params['__blockName']) ? $params['__blockName'] : $this->blockName($path));
		foreach ($this->helpers as $name => $obj) {
			if(!isset($params[$name])) {
				$params[$name] = $obj;
			}
		}
		return $params;
	}
	
	protected function dirnameFor($path)
	{
		if (preg_match('{^blade:(.+)$}', $path, $m)) {
			$path = $m[1];
			$path = rtrim($this->src, '/'). '/' . str_replace('.', '/', $path) . '/.php';
		}
		return dirname($path);
	}

	public function renderBlock($name, $params = array())
	{
		return $this->render($name, $params);
	}

	protected function find($name)
	{
		if ($this->env->isProd() && $template = $this->sourceMap->find($name)) {
			return $template;
		}

		$path = $this->templatePath($name);

		return $this->addToSourceMap($path, $name);
	}

	protected function addToSourceMap($path, $name)
	{
		if (substr($path, 0, 6) === 'blade:') {
			return $path;
		}
		
		if (!file_exists($this->src . $path)) {
			return $this->src . $path;
		}

		if ($this->env->isProd()) {
			$this->sourceMap->add($path, $name);
		}

		return $path;
	}
	
	public function exists($name)
	{
		$path = $this->templatePath($name);
		if (strpos($path, 'blade:') === 0) {
			return true;
		}
		return file_exists($this->src . $path);
	}

	private function npmModulePath($name)
	{
		return "/node_modules/" . ltrim($name, '@');
	}

	private function templatePath($name)
	{
		if (strpos($name, '@') === 0) {
			return $this->npmModulePath($name);
		}
		
		if (preg_match('{/([^/]+)$}', $name, $m)) {
			$path = rtrim($this->src, '/');
			$path = "{$path}/src/block/{$name}/{$m[1]}.blade.php";
			if (is_file($path)) {
				return 'blade:block.'.str_replace('/', '.', $name).'.'.$m[1];
			}
		}
		
		if (strpos($name, '.twig') === false) {
			return "/src/{$this->blockPath($name)}";
		}

		return "/src/$name";
	}

	private function blockPath($name)
	{
		return "block/$name/{$this->blockTemplate($name)}";
	}

	private function blockTemplate($name)
	{
		$parts = explode('/', $name);
		return end($parts) . ".html.twig";
	}

	protected function blockName($name)
	{
		if (preg_match('{^blade:(.+)\.([^.]+)$}', $name, $m)) {
			return $m[2];
		}
		$parts = explode('/', str_replace('.html.twig', '', $name));
		return end($parts);
	}

	/**
	 * Добавляет объект-хелпер с php-методами, помогающими рендерить контент
	 *
	 * @param object $helper
	 * @param string $name = 'app'
	 */
	public function addHelper($helper, $name = 'app')
	{
		$this->helpers[$name] = $helper;
	}
}
