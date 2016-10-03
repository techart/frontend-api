<?php
namespace Techart\Frontend;

function realpath($path)
{
    return $path;
}

class PathResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultAssetPathReturnedWhenNoOptionsGiven()
    {
        $frontendPath = 'test/frontend';
        $pathResolver = new PathResolver($frontendPath);
        $pathResolver->setConfigReader($this->getEmptyConfigReader()->getMock());
        $this->assertNotEmpty(trim(str_replace(realpath($frontendPath), '', $pathResolver->assetsPath()), '/'));
    }

    public function testAssetPathTakenFromOptions()
    {
        $path = '/test/frontend';
        $options = [
            'assetsPath' => 'test_assets',
        ];
        $pathResolver = new PathResolver($path, $options);
        $pathResolver->setConfigReader($this->getEmptyConfigReader()->getMock());
        $this->assertEquals(realpath($path) . '/' . $options['assetsPath'], $pathResolver->assetsPath());
    }

    public function testDocRootTakenFromServer()
    {
        $_SERVER['DOCUMENT_ROOT'] = $root = '/some/test/root/';
        $pathResolver = new PathResolver('/frontend');
        $pathResolver->setConfigReader($this->getEmptyConfigReader()->getMock());
        $this->assertEquals($root, $pathResolver->docRoot());
    }

    public function testDocRootTakenFromConfigWhenNoServer()
    {
        $_SERVER['DOCUMENT_ROOT'] = '';
        $frontendPath = '/frontend';
        $root = '../correct/doc/root/';
        $configReader = $this->getEmptyConfigReader()
            ->setMethods(['get'])
            ->getMock();
        $configReader->expects($this->any())
            ->method('get')
            ->with('docRoot')
            ->willReturn($root);
        $pathResolver = new PathResolver($frontendPath);
        $pathResolver->setConfigReader($configReader);
        $this->assertEquals($frontendPath . '/' . $root, $pathResolver->docRoot());
    }

    public function testPublicPathTakenFromConfig()
    {
        $frontendPath = '/var/www/frontend';
        $configReader = $this->getEmptyConfigReader()
            ->setMethods(['get'])
            ->getMock();
        $configReader->expects($this->atLeastOnce())
            ->method('get');
        $pathResolver = new PathResolver($frontendPath);
        $pathResolver->setConfigReader($configReader);
        $pathResolver->publicPath();
    }

    public function testSettingsPathTakenFromOptions()
    {
        $path = '/test/frontend';
        $options = [
            'settingsPath' => 'test_settings',
        ];
        $pathResolver = new PathResolver($path, $options);
        $pathResolver->setConfigReader($this->getEmptyConfigReader()->getMock());
        $this->assertEquals(realpath($path) . '/' . $options['settingsPath'], $pathResolver->settingsPath());
    }

    private function getEmptyConfigReader()
    {
        return $this->getMockBuilder('Techart\Frontend\ConfigReaderInterface');
    }
}