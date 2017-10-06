<?php

namespace Techart\Frontend;

interface EnvironmentInterface
{
	const PROD = 'prod';
	const DEV = 'dev';
	const HOT = 'hot';

	public function getName();

	public function isProd();

	public function isDev();

	public function isHot();

	public function switchTo($envName);
}
