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

	public function addGlobal($name, $value)
	{
		$this->twig->addGlobal($name, $value);
	}

	public function render($name, $params = array())
	{
		$path = $this->find($name);
		$params = $this->defaultParams($path, $params);

		return $this->twig->render($path, $params);
	}

	public function blockMacrosPath($name)
	{
		return "@block/$name/" . end(explode('/', $name)) . '.macros.twig';
	}

	protected function defaultParams($path, $params)
	{
		$params['__DIR__'] = dirname($path);
		//TODO: рассмотреть возможность подключения дополнительных хелперов, block сейчас вбит гвоздями
		$params['block'] = new Block(!empty($params['__blockName']) ? $params['__blockName'] : $this->blockName($path));
		return $params;
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

		return $this->exists($path, $name);
	}

	protected function exists($path, $name)
	{
		if (!file_exists($this->src . $path)) {
			return $this->src . $path;
		}

		if ($this->env->isProd()) {
			$this->sourceMap->add($path, $name);
		}

		return $path;
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
		return end(explode('/', $name)) . ".html.twig";
	}

	private function blockName($name)
	{
		return end(explode('/', str_replace('.html.twig', '', $name)));
	}
}
