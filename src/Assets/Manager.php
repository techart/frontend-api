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

use Techart\Frontend\ConfigReaderInterface;
use Techart\Frontend\ConfigReader;
use Techart\Frontend\EnvironmentInterface;

class Manager
{
    private $env;
    private $renderer;
    private $reader;
    private $configReader;
    private $pathResolver;

    /**
     * Assets constructor.
     *
     * @todo     move path resolving to another module
     *
     * @param EnvironmentInterface           $env
     * @param \Techart\Frontend\PathResolver $pathResolver
     *
     * @internal param string $frontendPath
     * @internal param $envName
     */
    public function __construct(EnvironmentInterface $env, $pathResolver)
    {
        $this->env = $env;
        $this->pathResolver = $pathResolver;
    }

    public function url($path)
    {
        $path = trim($path, '/');
        $env = ($this->env->isDev() || $this->env->isHot()) ? EnvironmentInterface::DEV : EnvironmentInterface::PROD;
        return "{$this->pathResolver->publicPath()}/{$env}/{$path}";
    }

    public function cssUrl($entryPointName)
    {
        return $this->reader()->get($entryPointName, 'css') ?: $this->getFallbackUrl($entryPointName, 'css');
    }

    public function jsUrl($entryPointName)
    {
        return $this->reader()->get($entryPointName, 'js') ?: $this->getFallbackUrl($entryPointName, 'js');
    }

    public function cssTag($name, $attrs = array())
    {
        return $this->includeFile('css', $name, $attrs);
    }

    public function jsTag($name, $attrs = array())
    {
        return $this->includeFile('js', $name, $attrs);
    }

    public function setReader(RendererInterface $reader)
    {
        $this->reader = $reader;
    }

    public function setRenderer(AssetsReaderInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    private function includeFile($type, $name, $attrs = array())
    {
        $url = $this->reader()->get($name, $type) ?: $this->getFallbackUrl($name, $type);
        return $url ? $this->renderer()->tag($type, $url, $attrs) : '';
    }

    public function getFallbackUrl($name, $type)
    {
        $url = "{$this->pathResolver->publicPath()}/{$this->env->getName()}/{$type}/{$name}.{$type}";
        return is_file("{$this->pathResolver->docRoot()}{$url}") ? $url : null;
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

    /**
     * @return ConfigReaderInterface
     */
    private function configReader()
    {
        return $this->configReader ?: $this->configReader = new ConfigReader($this->pathResolver->settingsPath());
    }
}
