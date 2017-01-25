<?php

namespace Techart\Frontend\Assets;

interface AssetsReaderInterface
{
    public function get($entryPointName, $type);

    public function getFirstPath();
}
