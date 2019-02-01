Пакет является php-интерфейсом для работы с tao-webpack. Он позволяет подключать скрипты, стили, получать URL файлов, компилировать
шаблоны.


## Установка

- Устанавливаем последнюю версию из репозитория: `composer require 'techart/frontend-api'`


## Использование

### Через обертку

* создаем окружение и `PathResolver`, передаем их в конструктор класса `Frontend`

```php
<?php
$env = new Techart\Frontend\Environment(new EnvEnvironmentStorage());
$pathResolver = new \Techart\Frontend\PathResolver('../frontend', ['twigCachePath' => '../twig']);

$frontend = new \Techart\Frontend\Frontend($env, $pathResolver);
```
* используем

```php
<?php
$frontend->assets()->cssUrl('index');

$frontend->templates()->render('page/main/main.html.twig', [
		'message' =>'That TWIG',
		'news' => CMS::orm()->news->range(10)->select()
], 'raw')
```

### по отдельности

* создаем окружение, `PathResolver` и менеджеры

```php
<?php
$env = new \Techart\Frontend\Environment(new EnvEnvironmentStorage());
$pathResolver = new \Techart\Frontend\PathResolver('./frontend');

$assetsManager = new Techart\Frontend\Assets\Manager($env, $pathResolver);

$templatesManager = new \Techart\Frontend\Templates\Manager(
    new Techart\Frontend\Templates\Repository(
        new Techart\Frontend\Templates\Factory($env, $pathResolver)
    )
);
```
* Используем

```php
<?php
$assetsManager->cssUrl('index');
$templatesManager->render('page/main/main.html.twig', [
	'message' =>'That TWIG',
	'news' => CMS::orm()->news->range(10)->select()
], 'raw')

```


подробнее см. [Пример кода](examples/test.php)