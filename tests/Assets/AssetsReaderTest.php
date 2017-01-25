<?php
namespace Techart\Frontend;

use Techart\Frontend\Assets\AssetsReader;

class AssetsReaderTest extends \PHPUnit_Framework_TestCase
{
    private $reader;

    public function setUp()
    {
        $env = $this->getMockBuilder('Techart\Frontend\EnvironmentInterface')->getMock();
        $env->expects($this->any())
            ->method('getName')
            ->willReturn('dev');
        $this->reader = new AssetsReader($env, './');
    }

    /**
     * @dataProvider content
     *
     * @param $content
     * @param $expected
     */
    public function testAssetsReaderCanGetFirstLine($content, $expected)
    {
        file_put_contents('./dev.json', $content);
        $this->assertEquals($expected, $this->reader->getFirstPath());
        unlink('./dev.json');
    }

    public function content()
    {
        return [
            [
                '{
  ".img": {
    "js": "/some/creepy/path"
  },
  "common": {
    "js": "/local/templates/nav-tn/builds/dev/js/common.js"
  },
  "index": {
    "js": "/local/templates/nav-tn/builds/dev/js/index.js",
    "css": "/local/templates/nav-tn/builds/dev/css/index.css"
  },
  "main": {
    "js": "/local/templates/nav-tn/builds/dev/js/main.js"
  }
}',
                '/some/creepy/path'
            ],
            [
                '{
  ".img": {
    "js": "http://some.host.ru/some/creepy/path"
  },
  "common": {
    "js": "/local/templates/nav-tn/builds/dev/js/common.js"
  },
  "index": {
    "js": "/local/templates/nav-tn/builds/dev/js/index.js",
    "css": "/local/templates/nav-tn/builds/dev/css/index.css"
  },
  "main": {
    "js": "/local/templates/nav-tn/builds/dev/js/main.js"
  }
}',
                'http://some.host.ru/some/creepy/path'
            ],
            [
                '{
  ".img": {
    "js": "http://some.host.ru:9999/some/creepy/path"
  }
}',
                'http://some.host.ru:9999/some/creepy/path'
            ],
        ];
    }
}
