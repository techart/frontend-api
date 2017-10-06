<?php

namespace Techart\Frontend;

interface EnvironmentStorageInterface
{
	const ENV_PARAM = '__env';
	const RUN_ENV_PARAM = '__run_env';
	const ENV_NAME = 'env';

	public function getFromConfig($name);

	public function getFromRequest($name);

	public function getFromSession($name);

	public function setToSession($name, $value);
}
