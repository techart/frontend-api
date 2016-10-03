<?php

include('./path-to-composer-autoload/autoload.php');

class EnvEnvironmentStorage implements \Techart\Frontend\EnvironmentStorageInterface
{
    public function getFromConfig($name)
    {
        return 'prod';
    }

    public function getFromRequest($name)
    {
        return 'dev';
    }

    public function getFromSession($name)
    {
        return 'dev';
    }

    public function setToSession($name, $value)
    {
        // do noting, for example
    }
}

$env = new \Techart\Frontend\Environment(new EnvEnvironmentStorage());
$pathResolver = new \Techart\Frontend\PathResolver('./frontend');

$api = new Techart\Frontend\Assets\Manager($env, $pathResolver);

var_dump($api->url('img/sprite/sprite.svg'));
var_dump($api->cssUrl('index'));
var_dump($api->jsUrl('index'));
var_dump($api->cssTag('index'));
var_dump($api->jsTag('index'));

$templatesManager = new \Techart\Frontend\Templates\Manager(
    new Techart\Frontend\Templates\Repository(
        new Techart\Frontend\Templates\Factory($env, $pathResolver)
    )
);

$templatesManager->render('page/main/main.html.twig', [
    'message' =>'That TWIG',
    'news' => CMS::orm()->news->range(10)->select()
], 'raw');

$templatesManager->render('page/main/partial.html.twig', [
    'message' =>'Partial',
]);

;
$templatesManager->renderBlock('custom/news-list', [
    'rows' => CMS::orm()->news->range(10)->select(),
    'closure' => new Techart\Frontend\Closure(function() {
        return 'from closure';
    }),
    'anotherClosure' => new Techart\Frontend\Closure([$obj, $method], $params),
    'callable' => function() {
        return 'from callable';
    }
], 'raw');

$templatesManager->addRenderer('debug', 'MyAwesomeRenderer', array('debug' => true));
$templatesManager->render('awesomeTemplate.html.twig', [
], 'debug');

$templatesManager->render('@npm-package/path/template.html.twig');

//то же самое чере обертку

$frontend = new \Techart\Frontend\Frontend($env, $pathResolver);

$frontend->assets()->cssUrl('index');

$frontend->templates()->render('page/main/main.html.twig', [
    'message' =>'That TWIG',
    'news' => CMS::orm()->news->range(10)->select()
], 'raw');

$frontend->templates()->renderBlock('custom/news-list', [
    'rows' => CMS::orm()->news->range(10)->select()
], 'raw');

// рендер шаблона npm модуля
$frontend->templates()->render('@npm-package/path/template.html.twig');

// использование замыканий
$frontend->templates()->renderBlock('template', [
    'closure' => $this->frontend()->closure(function() {
        return 'from closure';
    }),
    'anotherClosure' => $this->frontend()->closure([$obj, $method], $params),
    'callable' => function() {
        return 'from callable';
    }
], 'raw');


