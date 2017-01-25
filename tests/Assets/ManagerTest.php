<?php
namespace Techart\Frontend;

use Techart\Frontend\Assets\Manager;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider paths
     *
     * @param $path
     */
    public function testUrlBuildsPathWithRequestedPath($path)
    {
        $manager = new Manager($this->getEnvironment('prod'), $this->getPathResolver());
        $this->assertEquals("/builds/prod/{$path}", $manager->url($path));
    }

    public function testUrlBuildsPathWithBuildPath()
    {
        $manager = new Manager($this->getEnvironment('dev'), $this->getPathResolver('/not_builds'));

        $this->assertEquals(
            "/not_builds/dev/some/creep/image.jpg",
            $manager->url('some/creep/image.jpg')
        );
    }

    /**
     * @dataProvider envs
     *
     * @param $env
     */
    public function testUrlBuildsPathWithEnv($env)
    {
        $manager = new Manager($this->getEnvironment($env), $this->getPathResolver());

        $this->assertEquals(
            "/builds/{$env}/some/creep/image.jpg",
            $manager->url('some/creep/image.jpg')
        );
    }

    /**
     * @dataProvider domains
     *
     * @param $domain
     */
    public function testUrlBuildsPathWithHotDomainOnHot($domain)
    {

        $manager = new Manager($this->getEnvironment('hot'), $this->getPathResolver());
        $manager->setReader($this->getAssetsReader("http://{$domain}:8889/some_creepy/path/with/slashes/"));

        $this->assertEquals(
            "http://{$domain}:8889/builds/dev/some/creep/image.jpg",
            $manager->url('some/creep/image.jpg')
        );
    }

    /**
     * @dataProvider ports
     *
     * @param $hotPort
     */
    public function testUrlBuildsPathWithHotPortOnHot($hotPort)
    {
        $reader = $this->getAssetsReader("http://somedomain.ru.some.machine:{$hotPort}/some_creepy/path/with/slashes/");

        $manager = new Manager($this->getEnvironment('hot'), $this->getPathResolver());
        $manager->setReader($reader);

        $this->assertEquals(
            "http://somedomain.ru.some.machine:{$hotPort}/builds/dev/some/creep/image.jpg",
            $manager->url('some/creep/image.jpg')
        );
    }

    /**
     * @dataProvider domains
     *
     * @param $domain
     */
    public function testUrlBuildsPathFromDevDomainOnHot($domain)
    {
        $reader = $this->getAssetsReader("http://{$domain}:8889/some_creepy/path/with/slashes/");
        $manager = new Manager($this->getEnvironment('hot'), $this->getPathResolver());
        $manager->setReader($reader);

        $this->assertEquals(
            "http://{$domain}:8889/builds/dev/some/creep/image.jpg",
            $manager->url('some/creep/image.jpg')
        );
    }

    public function paths()
    {
        return [
            ['some/creep/image.jpg'],
            ['some/creep/image1.jpg'],
            [''],
        ];
    }

    public function domains()
    {
        return [
            ['somedomain.ru'],
            ['somecreep.ru'],
            ['somedomain.somemachine.ru.test'],
        ];
    }

    public function ports()
    {
        return [
            ['8889'],
            ['8887'],
            ['1'],
        ];
    }

    public function envs()
    {
        return [
            ['dev'],
            ['prod'],
        ];
    }

    private function getEnvironment($envName)
    {
        $env = $this->getMockBuilder('Techart\Frontend\EnvironmentInterface')
            ->getMock();
        $env->expects($this->any())
            ->method('getName')
            ->willReturn($envName);
        $env->expects($this->any())
            ->method('is' . strtoupper($envName))
            ->willReturn(true);
        return $env;
    }

    private function getPathResolver($builds_dir = '/builds')
    {
        $resolver = $this->getMockBuilder('Techart\Frontend\PathResolver')
            ->setConstructorArgs(['/some/path/'])
            ->getMock();
        $resolver->expects($this->any())
            ->method('publicPath')
            ->willReturn($builds_dir);
        return $resolver;
    }

    private function getAssetsReader($firstPath)
    {
        $reader = $this->getMockBuilder('Techart\Frontend\Assets\AssetsReaderInterface')->getMock();
        $reader->expects($this->any())
            ->method('getFirstPath')
            ->willReturn($firstPath);
        return $reader;
    }
}