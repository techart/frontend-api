<?php

namespace Techart\Frontend\Assets;

/**
 * Интерфейс класса, реализующего чтение файла assets
 *
 * @package Techart\Frontend\Assets
 */
interface AssetsReaderInterface
{
	/**
	 * @param string $entryPointName
	 * @param string $type
	 * @return mixed
	 */
	public function get($entryPointName, $type);

	/**
	 * @return mixed
	 */
	public function getFirstPath();
}
