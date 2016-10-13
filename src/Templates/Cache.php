<?php

namespace Techart\Frontend\Templates;

class Cache extends \Twig_Cache_Filesystem
{
	public function write($key, $content)
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
