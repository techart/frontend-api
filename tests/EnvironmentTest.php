<?php
namespace Techart\Frontend;

class EnvironmentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider defaultEnvironmentProvider
     */
    public function testDefaultEnvironmentParameterUsed($defaultEnv, $expectedEnv)
    {
        $env = new Environment($this->getEmptyStorage(), $defaultEnv);
        $this->assertEquals($expectedEnv, $env->getName());
    }

    /**
     * @dataProvider defaultEnvironmentProvider
     */
    public function testEnvSwitches($switchEnv)
    {
        $env = new Environment($this->getEmptyStorage());
        $env->switchTo($switchEnv);
        $this->assertEquals($switchEnv, $env->getName());
    }

    public function defaultEnvironmentProvider()
    {
        return [
            [ EnvironmentInterface::PROD, EnvironmentInterface::PROD ],
            [ EnvironmentInterface::DEV, EnvironmentInterface::DEV ],
            [ EnvironmentInterface::HOT, EnvironmentInterface::HOT ],
        ];
    }

    public function testIsProdReturnsTrueOnProdEnv()
    {
        $env = new Environment($this->getEmptyStorage());
        $env->switchTo(EnvironmentInterface::PROD);
        $this->assertTrue($env->isProd());
    }

    public function testIsProdReturnsFalseOnNonProdEnv()
    {
        $env = new Environment($this->getEmptyStorage());
        $env->switchTo(EnvironmentInterface::DEV);
        $this->assertFalse($env->isProd());
    }

    public function testIsDevReturnsTrueOnDevEnv()
    {
        $env = new Environment($this->getEmptyStorage());
        $env->switchTo(EnvironmentInterface::DEV);
        $this->assertTrue($env->isDev());
    }

    public function testIsDevReturnsFalseOnNonDevEnv()
    {
        $env = new Environment($this->getEmptyStorage());
        $env->switchTo(EnvironmentInterface::PROD);
        $this->assertFalse($env->isDev());
    }

    public function testIsHotReturnsTrueOnHotEnv()
    {
        $env = new Environment($this->getEmptyStorage());
        $env->switchTo(EnvironmentInterface::HOT);
        $this->assertTrue($env->isHot());
    }

    public function testIsHotReturnsFalseOnNonHotEnv()
    {
        $env = new Environment($this->getEmptyStorage());
        $env->switchTo(EnvironmentInterface::DEV);
        $this->assertFalse($env->isHot());
    }

    public function testEnvFromConfigUsed()
    {
        $storage = $this->getMockBuilder('Techart\Frontend\EnvironmentStorageInterface')
            ->setMethods(['getFromConfig', 'getFromRequest', 'getFromSession', 'setToSession'])
            ->getMock();
        $storage->expects($this->atLeastOnce())
            ->method('getFromConfig')
            ->with('env')
            ->willReturn(EnvironmentInterface::DEV);
        $env = new Environment($storage);
        $this->assertTrue($env->isDev());
    }

    public function testEnvFromConfigPriorityIfProd()
    {
        $storage = $this->getMockBuilder('Techart\Frontend\EnvironmentStorageInterface')
            ->setMethods(['getFromConfig', 'getFromRequest', 'getFromSession', 'setToSession'])
            ->getMock();
        $storage->expects($this->atLeastOnce())
            ->method('getFromConfig')
            ->with('env')
            ->willReturn(EnvironmentInterface::PROD);
        $storage->expects($this->any())
            ->method('getFromRequest')
            ->willReturn(EnvironmentInterface::DEV);
        $env = new Environment($storage);
        $this->assertTrue($env->isProd());
    }

//    public function testEnvFromRequestUsed()
//    {
//        $storage = $this->getMockBuilder('Techart\Frontend\EnvironmentStorageInterface')
//            ->setMethods(['getFromConfig', 'getFromRequest', 'getFromSession', 'setToSession'])
//            ->getMock();
//        $storage->expects($this->atLeastOnce())
//            ->method('__run_env')
//            ->with('env')
//            ->willReturn(EnvironmentInterface::DEV);
//        $env = new Environment($storage);
//        $this->assertTrue($env->isDev());
//    }

    private function getEmptyStorage()
    {
        $storage = $this->getMockBuilder('Techart\Frontend\EnvironmentStorageInterface')
            ->setMethods(['getFromConfig', 'getFromRequest', 'getFromSession', 'setToSession'])
            ->getMock();
        $storage->expects($this->any())
            ->method('getFromConfig')->willReturn(null);
        $storage->expects($this->any())
            ->method('getFromRequest')->willReturn(null);
        $storage->expects($this->any())
            ->method('getFromSession')->willReturn(null);
        $storage->expects($this->any())
            ->method('setToSession')->willReturn(null);
        return $storage;
    }
}
