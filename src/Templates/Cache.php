<?php

namespace Techart\Frontend\Templates;

class Cache extends \Twig\Cache\FilesystemCache
{
	public function write(string $key, string $content):void
	{
		$old = umask(2);
		try {
			parent::write($key, $content);
			umask($old);
		} catch (\RuntimeException $e) {
			umask($old);
			throw new \RuntimeException($e->getMessage());
		}
	}
}
