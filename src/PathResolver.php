<?php

namespace Techart\Frontend;

class PathResolver
{
    private $frontendPath;
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

    public function assetsPath()
    {
        return $this->assetsPath ?: $this->assetsPath = $this->frontendPath . $this->getOption('assetsPath');
    }

    public function docRoot()
    {
        if (!$this->docRoot) {
            $this->docRoot = $this->options['docRoot'] ?
                $this->options['docRoot'] :
                ($_SERVER['DOCUMENT_ROOT'] ?: realpath($this->frontendPath . $this->configReader()->get('docRoot')));
            $this->docRoot = rtrim($this->docRoot, '/');
        }
        return $this->docRoot;
    }

    public function publicPath()
    {
        return $this->publicPath ?: $this->publicPath = str_replace($this->docRoot(), '', realpath($this->frontendPath . $this->configReader()->get('buildPath')));
    }

    public function settingsPath()
    {
        return $this->settingsPath ?: $this->settingsPath = $this->frontendPath . $this->getOption('settingsPath');
    }

    public function frontendPath()
    {
        return $this->frontendPath;
    }

    public function twigCachePath()
    {
        return $this->getOption('twigCachePath');
    }

    public function setConfigReader(ConfigReaderInterface $configReader)
    {
        $this->configReader = $configReader;
        return $this;
    }

    private function getOption($name)
    {
        return !empty($this->options[$name]) ? $this->options[$name] : null;
    }

    private function configReader()
    {
        return $this->configReader ?: $this->configReader = new ConfigReader($this->settingsPath());
    }
}