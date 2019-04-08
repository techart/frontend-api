<?php

namespace Techart\Frontend\Templates;

use Illuminate\Contracts\View\Factory as FactoryInterface;
use Illuminate\Contracts\View\View as ViewInterface;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Compilers\CompilerInterface;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\View\View;

class Blade
{
	protected $path;
	protected $cache;
	protected $factory;
	protected $finder;
	
	public function __construct(string $path, $cache = null)
	{
		$this->path = $path;
		
		if (!$cache) {
			$cache = '../cache/blade';
		}
		$this->cache = $cache;
	}
	
	public function getFinder()
	{
		if (!$this->finder) {
			$this->finder = new FileViewFinder(new Filesystem, [$this->path]);
		}
		
		return $this->finder;
	}
	
	public function setFinder($finder)
	{
		$this->finder = $finder;
	}
	
	public function render(string $view, array $params = [])
	{
		return $this->make($view, $params)->render();
	}
	
	public function make($view, $params = [], $mergeData = [])
	{
		return $this->getFactory()->make($view, $params, $mergeData);
	}
	
	private function getFactory()
	{
		if ($this->factory) {
			return $this->factory;
		}
		
		$resolver = new EngineResolver;
		$resolver->register("blade", function () {
			if (!is_dir($this->cache)) {
				mkdir($this->cache, 0777, true);
			}
			
			$blade = new BladeCompiler(new Filesystem, $this->cache);
			return new CompilerEngine($blade);
		});
		
		$this->factory = new Factory($resolver, $this->getFinder(), new Dispatcher);
		
		return $this->factory;
	}
	
	public function extend(callable $compiler)
	{
		$this
			->getCompiler()
			->extend($compiler);
			
		return $this;
	}
	
	public function getCompiler()
	{
		return $this
			->getFactory()
			->getEngineResolver()
			->resolve("blade")
			->getCompiler();
	}
	
	public function directive(string $name, callable $handler)
	{
		$this
			->getCompiler()
			->directive($name, $handler);
			
		return $this;
	}
	
	public function addPath(string $path)
	{
		$this->getFinder()->addLocation($path);
		
		return $this;
	}
	
	public function exists($view)
	{
		return $this->getFactory()->exists($view);
	}

	public function addGlobal($name, $value)
	{
		$this->getFactory()->share($name, $value);
	}
}
