<?php

namespace Techart\Frontend\Assets;

use Techart\Frontend\EnvironmentInterface;

class AssetsReader implements AssetsReaderInterface
{
    private $env;
    private $assetsDir;
    private $jsonData = array();

    public function __construct(EnvironmentInterface $env, $assetsDir)
    {
        $this->env = $env;
        $this->assetsDir = $assetsDir;
    }

    public function get($entryPointName, $type)
    {
        $this->readJson();
        return !empty($this->jsonData[$entryPointName][$type]) ? $this->jsonData[$entryPointName][$type] : null;
    }

    public function getFirstPath()
    {
        $this->readJson();
        return reset(reset($this->jsonData));
    }

    private function readJson()
    {
        if (empty($this->jsonData) && $path = $this->getJsonPath()) {
            $this->jsonData = json_decode(file_get_contents($path), true);
        }
    }

    private function getJsonPath()
    {
        return realpath($this->assetsDir . '/' . $this->env->getName() . '.json');
    }
}
