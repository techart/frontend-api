<?php

namespace Techart\Frontend;

class Environment implements EnvironmentInterface
{
	private $storage;
	private $defaultEnvName;
	private $name;

	public function __construct(EnvironmentStorageInterface $storage, $defaultEnvName = self::PROD)
	{
		$this->storage = $storage;
		$this->defaultEnvName = $defaultEnvName;
	}

	public function getName()
	{
		return $this->name ?: $this->name = $this->detectEnvironment();
	}

	public function isProd()
	{
		return $this->getName() == self::PROD;
	}

	public function isDev()
	{
		return $this->getName() == self::DEV;
	}

	public function isHot()
	{
		return $this->getName() == self::HOT;
	}

	public function switchTo($envName)
	{
		$this->name = $envName;
	}

	private function detectEnvironment()
	{
		$env_from_config = $this->getFromConfig();
		if ($env_from_config == self::PROD) {
			return $env_from_config;
		}
		if ($env_from_request = $this->getFromRequest()) {
			return $env_from_request;
		}
		if ($env_from_session = $this->getFromSession()) {
			return $env_from_session;
		}
		return $env_from_config ?: $this->defaultEnvName;
	}

	private function getFromConfig()
	{
		return $this->storage->getFromConfig(EnvironmentStorageInterface::ENV_NAME);
	}

	private function getFromRequest()
	{
		return $this->storage->getFromRequest(EnvironmentStorageInterface::RUN_ENV_PARAM);
	}

	private function getFromSession()
	{
		$fromRequest = $this->storage->getFromRequest(EnvironmentStorageInterface::ENV_PARAM);
		$fromSession = $this->storage->getFromSession(EnvironmentStorageInterface::ENV_PARAM);
		if ($fromRequest && $fromSession != $fromRequest) {
			$this->storage->setToSession(EnvironmentStorageInterface::ENV_PARAM, $fromRequest);
			return $fromRequest;
		}
		return $fromSession;
	}
}
