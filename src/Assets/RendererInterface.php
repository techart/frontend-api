<?php

namespace Techart\Frontend\Assets;

interface RendererInterface
{
	public function tag($type, $url, $attrs = array());
}