<?php
/**
 * 1. Environment [+]
 * 2. publicPath читать из frontend [+]
 * 3. конфиг для Assets [+]
 * 4. перетащить staticUrl [+]
 * 6. Перетащить сюда templates [+]
 * 7. EnvStorage - выбор переменной в Env [+]
 * 8. Env - кэшить поиск имени, сеттер имени, очистка кэша имени [+]
 * 9999. Wiki
 *
 * Templates
 * 1. Путь к кастомным шаблонам (от папки с frontend, например)
 * 2. Путь к кэшу [+]
 * 3. Очистка кэша [+]
 * 9999. Wiki
 */

namespace Techart\Frontend\Assets;

use Techart\Frontend\EnvironmentInterface;

class Manager
{
	private $env;
	private $renderer;
	private $reader;
	private $pathResolver;

	/**
	 * Assets constructor.
	 *
	 * @param EnvironmentInterface           $env
	 * @param \Techart\Frontend\PathResolver $pathResolver
	 */
	public function __construct(EnvironmentInterface $env, $pathResolver)
	{
		$this->env = $env;
		$this->pathResolver = $pathResolver;
	}

	/**
	 * Получение адреса до произвольного файла из сборки
	 *
	 * @param string $path
	 * @return string
	 */
	public function url($path)
	{
		$maxImgSize = $this->pathResolver->configReader()->get('base64MaxFileSize');
		$path = trim($path, '/');
		$env = ($this->env->isDev() || $this->env->isHot()) ? EnvironmentInterface::DEV : EnvironmentInterface::PROD;

		if (filesize( $this->pathResolver->frontendPath() . $path) >= $maxImgSize) {
			if ($this->env->isHot()) {
				preg_match('~^((.+)://([^/]+))/(.*)~', $this->reader()->getFirstPath(), $m);
				return "{$m[1]}{$this->pathResolver->publicPath()}/{$env}/{$path}";
			}
			return "{$this->pathResolver->publicPath()}/{$env}/{$path}";
		} else {
			return $this->getBase64($this->pathResolver->frontendPath() . $path);
		}
	}

	protected function getBase64($path) {
		$type = pathinfo($path, PATHINFO_EXTENSION);
		if ($type === 'svg') {
			$type .= '+xml';
		}
		$data = file_get_contents($path);
		return 'data:image/' . $type . ';base64,' . base64_encode($data);
	}

	/**
	 * Получение адреса стилей для заданной точки входа
	 *
	 * @param string $entryPointName
	 * @return null|string
	 */
	public function cssUrl($entryPointName)
	{
		return $this->reader()->get($entryPointName, 'css') ?: $this->getFallbackUrl($entryPointName, 'css');
	}

	/**
	 * Получение адреса скрита для заданной точки входа
	 *
	 * @param string $entryPointName
	 * @return null|string
	 */
	public function jsUrl($entryPointName)
	{
		return $this->reader()->get($entryPointName, 'js') ?: $this->getFallbackUrl($entryPointName, 'js');
	}

	/**
	 * Получение html тэга для подключения стилей заданной точки входа
	 *
	 * @param string $entryPointName
	 * @param array  $attrs
	 * @return string
	 */
	public function cssTag($entryPointName, $attrs = array())
	{
		return $this->includeFile('css', $entryPointName, $attrs);
	}

	/**
	 * Получение html тэга для подключения скрипта заданной точки входа
	 *
	 * @param string $entryPointName
	 * @param array  $attrs
	 * @return string
	 */
	public function jsTag($entryPointName, $attrs = array())
	{
		return $this->includeFile('js', $entryPointName, $attrs);
	}

	/**
	 * Установка собственного класса реализующего интерфейс чтения файла assets
	 *
	 * @param AssetsReaderInterface $reader
	 */
	public function setReader(AssetsReaderInterface $reader)
	{
		$this->reader = $reader;
	}

	/**
	 * Установка собственного класса реализующего интерфейс генерации html-тегов
	 *
	 * @param RendererInterface $renderer
	 */
	public function setRenderer(RendererInterface $renderer)
	{
		$this->renderer = $renderer;
	}

	/**
	 * @param string $name
	 * @param string $type
	 * @return null|string
	 */
	public function getFallbackUrl($name, $type)
	{
		$url = "{$this->pathResolver->publicPath()}/{$this->env->getName()}/{$type}/{$name}.{$type}";
		return is_file("{$this->pathResolver->docRoot()}{$url}") ? $url : null;
	}

	/**
	 * @param string $type
	 * @param string $entryPointName
	 * @param array  $attrs
	 * @return string
	 */
	private function includeFile($type, $entryPointName, $attrs = array())
	{
		$url = $this->reader()->get($entryPointName, $type) ?: $this->getFallbackUrl($entryPointName, $type);
		return $url ? $this->renderer()->tag($type, $url, $attrs) : '';
	}

	/**
	 * @return RendererInterface
	 */
	private function renderer()
	{
		return $this->renderer ?: $this->renderer = new Renderer();
	}

	/**
	 * @return AssetsReaderInterface
	 */
	private function reader()
	{
		return $this->reader ?: $this->reader = new AssetsReader($this->env, $this->pathResolver->assetsPath());
	}
}
