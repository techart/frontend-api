<?php

namespace Techart\Frontend\Templates;

interface RendererInterface
{
	public function render($name, $params = array());
	public function renderBlock($name, $params = array());
}