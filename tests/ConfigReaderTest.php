<?php
namespace Techart\Frontend;

class ConfigReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildPathReaderFromFile()
    {
        $configReader = new ConfigReader('php://memory');
        $configReader->setContent('module.exports = {
	port: 8888,
	docRoot: "../www",
	buildPath: "../www/test",
	hotPort: 8889,
	mainStyleType: "less"
};');
        $this->assertEquals('../www/test', $configReader->get('buildPath'));
    }

    /**
     * @dataProvider docRootDataProvider
     */
    public function testDocRootReaderFromFile($content, $expected)
    {
        $configReader = new ConfigReader('php://memory');
        $configReader->setContent($content);
        $this->assertEquals($expected, $configReader->get('docRoot'));
    }

    public function docRootDataProvider()
    {
        return [
            [
                'module.exports = {
	port: 8888,
	docRoot: "../www/test",
	buildPath: "../www/builds",
	hotPort: 8889,
	mainStyleType: "less"
};',
                '../www/test'
            ],
            [
                'module.exports = {
	port: 8888,
	docRoot: \'../www/test\',
	buildPath: "../www/builds",
	hotPort: 8889,
	mainStyleType: "less"
};',
                '../www/test'
            ],
            [
                'module.exports = {
	port: 8888,
	buildPath: "../www/builds",
	hotPort: 8889,
	mainStyleType: "less"
};',
                ''
            ],
        ];
    }

    public function testNullReturnedOnUnknownProperties()
    {
        $configReader = new ConfigReader('php://memory');
        $configReader->setContent('module.exports = {
	port: 8888,
	docRoot: "../www/test",
	buildPath: "../www/builds",
	hotPort: 8889,
	mainStyleType: "less"
};');
        $this->assertNull($configReader->get('someCreepyUnexistedTestProperty'));
    }

    public function testEmptyStringReturnsOnKnownUnexistedProperties()
    {
        $configReader = new ConfigReader('php://memory');
        $configReader->setContent('module.exports = {
	port: 8888,
	buildPath: "../www/builds",
	hotPort: 8889,
	mainStyleType: "less"
};');
        $this->assertEquals('', $configReader->get('docRoot'));
    }
}
