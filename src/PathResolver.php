<?php

namespace Techart\Frontend;

class PathResolver
{
	private $frontendPath;
	private $options;
	private $assetsPath;
	private $docRoot;
	private $publicPath;
	private $settingsPath;
	private $configReader;
	private $defaultOptions = array(
		'assetsPath' => 'assets',
		'settingsPath' => 'user.settings.js',
		'twigCachePath' => 'some/',
		'docRoot' => '',
	);

	public function __construct($frontendPath, $options = array())
	{
		$this->frontendPath = rtrim(realpath($frontendPath), '/') . '/';
		$this->options = array_replace_recursive($this->defaultOptions, $options);
	}

	public function frontendPath()
	{
		return $this->frontendPath;
	}

	public function assetsPath()
	{
		if(!$this->assetsPath) {
			$this->assetsPath = $this->frontendPath . $this->getOption('assetsPath');
		}
		return $this->assetsPath;
	}

	public function docRoot()
	{
		if (!$this->docRoot) {
			$docRoot = $this->getOption('docRoot');
			if(!$docRoot) {
				$docRoot = $_SERVER['DOCUMENT_ROOT'];
			}
			if(!$docRoot) {
				$docRoot = realpath($this->frontendPath . $this->configReader()->get('docRoot'));
			}
			$this->docRoot = rtrim($docRoot, '/');
		}
		return $this->docRoot;
	}

	public function publicPath()
	{
		if(!$this->publicPath) {
			$path = realpath($this->frontendPath . $this->configReader()->get('buildPath'));
			$this->publicPath = str_replace($this->docRoot(), '', $path);
		}
		return $this->publicPath;
	}

	public function settingsPath()
	{
		if(!$this->settingsPath) {
			$this->settingsPath = $this->frontendPath . $this->getOption('settingsPath');
		}
		return  $this->settingsPath;
	}

	public function twigCachePath()
	{
		return $this->getOption('twigCachePath');
	}

	private function getOption($name)
	{
		return !empty($this->options[$name]) ? $this->options[$name] : null;
	}

	public function setConfigReader(ConfigReaderInterface $configReader)
	{
		$this->configReader = $configReader;
		return $this;
	}

	public function configReader()
	{
		if(!$this->configReader) {
			$this->configReader = new ConfigReader($this->settingsPath());
		}
		return $this->configReader;
	}
}
