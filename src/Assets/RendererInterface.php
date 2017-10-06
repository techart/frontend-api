<?php

namespace Techart\Frontend\Assets;

/**
 * Интерфейс класса, реализующего генерацию тега с сылкой на ресурсы заданного типа
 *
 * @package Techart\Frontend\Assets
 */
interface RendererInterface
{
	/**
	 * @param string $type  тип ресурса
	 * @param string $url   адресс ресурса
	 * @param array  $attrs прочие атрибуты
	 * @return string
	 */
	public function tag($type, $url, $attrs = array());
}
