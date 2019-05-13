<?php

namespace Techart\Frontend;

class ConfigReader implements ConfigReaderInterface
{
	private $configPath;
	private $config = array();
	private $content;

	public function __construct($configPath)
	{
		$this->configPath = $configPath;
	}

	public function get($name)
	{
		return !empty($this->config[$name]) ? $this->config[$name] : $this->config[$name] = $this->load($name);
	}

	/**
	 * @param $content
	 */
	public function setContent($content)
	{
		$this->content = $content;
	}

	private function load($name)
	{
		$content = $this->getFileContent();
		switch ($name) {
			case 'buildPath':
				return preg_match('~buildPath: [\'\"](.+)[\'\"],~m', $content, $m) ? $m[1] : '';
			case 'docRoot':
				return preg_match('~docRoot: [\'\"](.+)[\'\"],~m', $content, $m) ? $m[1] : '';
			case 'base64MaxFileSize':
				return preg_match('~base64MaxFileSize: (\d+),~m', $content, $m) ? intval($m[1]) : 0;
			default:
				return null;
		}
	}

	private function getFileContent()
	{
		return $this->content ?: $this->content = file_get_contents($this->configPath);
	}
}
