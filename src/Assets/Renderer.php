<?php

namespace Techart\Frontend\Assets;

class Renderer implements RendererInterface
{
	private $defaultAttrs = array(
		'css' => array(
			'media' => 'screen',
			'rel' => 'stylesheet',
		),
		'js' => array(),
	);

	/**
	 * Renderer constructor.
	 *
	 * @param array $defaultAttrs
	 */
	public function __construct($defaultAttrs = array())
	{
		$this->defaultAttrs = array_replace_recursive($this->defaultAttrs, $defaultAttrs);
	}

	public function tag($type, $url, $attrs = array())
	{
		switch ($type) {
			case 'css':
				return "<link href=\"$url\" {$this->attrsToString($this->attrs($attrs, $type))}>";
			case 'js':
				return "<script src=\"$url\" {$this->attrsToString($this->attrs($attrs, $type))}></script>";
			default:
				return '';
		}
	}

	private function attrs($attrs, $type)
	{
		return array_replace_recursive($this->defaultAttrs[$type], $attrs);
	}

	private function attrsToString($attrs)
	{
		$attrsString = '';
		foreach ($attrs as $name => $value) {
			$attrsString .= " {$name}=\"{$value}\"";
		}
		return trim($attrsString);
	}
}
