# PHP интерфейс к системе сборки фронтенда

Тут API для системы сборки фротенда, позволяющая просто подключать скрипты, стили, получать URL файлов, компилировать
шаблоны.


## Установка

- Добавляем в composer.json проекта наш локальный репозиторий (если ещё не добавлен)

```json
{
    "repositories": [
        {
            "type": "composer",
            "url": "http://composer.gitlab.s.intranet/"
        }
    ]
}
```

- Устанавливаем: `composer require 'techart/frontend-api'`


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


подробнее см. [Wiki](wikis/home)

или [Пример кода](examples/test.php)