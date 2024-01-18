<?php

namespace Techart\Frontend;

/**
 * Класс для работы с созданием storybook шаблонов для frontend блоков
 */
class Storybook {
	const ESC_GREEN_START = "\e[32m";
	const ESC_RED_START = "\e[33m";
	const ESC_FINISH = "\e[0m";
	const PREFIX = '... ';

	const FS_DIRS = ['.', '..'];

	private $rootPath;
	private $blockName;
	private $srcPath;
	private $blockLocal;
	private $blockPath;
	private $storyName;
	private $storyPath;
	private $params;
	private $templates;


	/**
	 * Возвращает массив параметров командной строки генератора
	 *
	 * @return array
	 */
	public static function getParams()
	{
		return [
			'help' => [
				'info' => 'выводит справку по параметрам',
			],

			'overwrite' => [
				'info' => 'указывает генератору, следует ли перезаписывать уже существующие файлы историй; возможные значения - yes или no',
				'variants' => ['yes', 'no'],
				'default' => 'no',
				'value' => '',
			],

			'only' => [
				'info' => 'указывает генератору, для каких блоков следует перегенерировать истории; принимает имена блоков как для вызова renderBlock(), несколько блоков можно указать через запятую',
				'value' => '',
			],
		];
	}


	/**
	 * Выводит в консоль справку по использованию генератора с параметрами
	 *
	 * @return void
	 */
	public static function showHelp()
	{
		echo 'Справка по параметрам запуска сценария:', PHP_EOL, PHP_EOL;

		foreach (static::getParams() as $name => $data) {
			$delim = str_repeat(' ', 12 - strlen($name));
			echo '  --', $name, $delim, $data['info'];
			if (isset($data['default'])) {
				echo ', значение по умолчанию - ', $data['default'];
			}
			echo PHP_EOL, PHP_EOL;
		}
	}


	/**
	 * Возвращает массив путей к файлам *.storybook.json из указанного каталога
	 *
	 * @param  string $dir путь к каталогу, в котором надо искать файлы
	 * @return array
	 */
	public static function scanStorybookJson($dir, $only_blocks = [])
	{
		$needs_check = (0 < count($only_blocks));

		$files = [];

		$_parts = explode('/', trim($dir, '/'));
		$blockName = end($_parts);

		$storybookFile = $blockName . '.storybook.json';

		$folder = scandir($dir);

		foreach ($folder as $item) {
			if (in_array($item, static::FS_DIRS)) {
				continue;
			}

			$itemPath = $dir . "/" . $item;
			if (is_dir($itemPath)) {
				$subitems = static::scanStorybookJson($itemPath, $only_blocks);
				$files = array_merge($files, $subitems);
			} else {
				if ($storybookFile === $item) {
					if ($needs_check) {
						$parts = array_slice(explode('/', $dir), -2);
						$block = implode('/', $parts);
						$group = implode('/', [$parts[0], '*']);
						if (!in_array($block, $only_blocks) &&
							!in_array($group, $only_blocks)) {
							continue;
						}
					}
					$files[] = $itemPath;
				}
			}
		}

		return $files;
	}


	/**
	 * Конструктор
	 *
	 * @param string $rootPath путь к корню сайта (каталог www)
	 * @return void
	 */
	public function __construct($rootPath)
	{
		$this->rootPath = $rootPath;
	}


	/**
	 * Выполняет основную логику работы:
	 * - собирает список файлов с описаниями историй блоков
	 * - для каждого найденного файла:
	 * -- формирует на основе описания настройки и параметры
	 * -- устанавливает их во внутренние переменные
	 * -- запускает генератор
	 *
	 * @param array $options настройки работы из аргументов командной строки
	 * @return void
	 */
	public function Run($options = [])
	{
		$all_overwrite = $options['all_overwrite'] ?? null;
		$only_blocks = $options['only_blocks'] ?? [];

		if ($all_overwrite) {
			echo PHP_EOL, static::ESC_RED_START, "ВНИМАНИЕ!!! Включен режим автоматической перезаписи всех файлов для историй блоков!", static::ESC_FINISH, PHP_EOL, PHP_EOL;
		}

		// Читаем .workspace_config
		$config = file_get_contents($this->rootPath . "/.workspace_config");

		// Достаём путь до папки src
		$matches = [];
		preg_match("/^(path_to_frontend|path_to_mordor)(.*)$/im", $config, $matches);
		$srcPath = "{$this->rootPath}/" . trim($matches[2]) . "/src";

		// Узнаём название проекта
		$matches = [];
		preg_match("/^project\s+(.*)$/im", $config, $matches);
		$projectName = trim($matches[1]);

		// Получаем список всех блоков для генерации
		$jsonFiles = self::scanStorybookJson("{$srcPath}/block", $only_blocks);

		if (0 < count($jsonFiles)) {
			echo 'Генерация историй Storybook...', PHP_EOL, PHP_EOL;

			foreach ($jsonFiles as $jsonFile) {
				try {
					$blockData = json_decode(file_get_contents($jsonFile), true);

					if (!$blockData ||
						!is_array($blockData) ||
						(0 === count($blockData))) {
						echo '!!! Не удалось прочитать данные из файла ', basename($jsonFile), PHP_EOL, PHP_EOL;
						continue;
					}

					$blockName = $blockData['block'];
					echo '... ', $blockName, PHP_EOL;

					$params = [
						'block' => $blockName,
						'section' => isset($blockData['section']) ? $blockData['section'] : $projectName,
						'title' => isset($blockData['title']) ?  $blockData['title'] : $blockName,
						'parameters' => [],
						'htmls' => [],
						'args' => [],
						'controls' => [],
						'flags' => [
							'all_overwrite' => &$all_overwrite,
						],
					];

					$paramNames = [
						'backgrounds',
						'layout',
					];

					foreach ($paramNames as $paramName) {
						if (isset($blockData[$paramName])) {
							$params['parameters'][$paramName] = $blockData[$paramName];
						}
					}

					foreach ($blockData['variants'] as $variantName => $variantParams) {
						$tempVars = [];
						$phpVars = [];
						foreach ($variantParams['controls'] as $paramName => $paramData) {
							$_control = null;
							$value = isset($paramData['value']) ? $paramData['value'] : '';
							if (is_array($value)) {
								if (isset($value['control']) &&
									(false === $value['control'])) {
									$_control = false;
									$value = '';
								} else {
									$_control = $value;
									$value = '';
								}
							}

							if (false !== $_control) {
								if (!isset($params['args'][$variantName])) {
									$params['args'][$variantName] = [];
								}
								if (!isset($params['controls'][$variantName])) {
									$params['controls'][$variantName] = [];
								}

								$params['args'][$variantName][$paramName] = $value;

								$value = isset($paramData['name']) ? ['name' => $paramData['name']] : [];
								if ($_control && is_array($_control)) {
									$value = array_merge($_control, $value);
								}
								$params['controls'][$variantName][$paramName] = $value;
							}

							if (isset($paramData['php'])) {
								$_value = trim($paramData['php']);
								if (';' !== mb_substr($_value, -1)) {
									$_value .= ';';
								}
								$tempVars[$paramName] =  eval('return ' . $_value);
								$phpVars[] = '"' . $paramName . '" => ' . $paramData['php'];
							} else if (isset($paramData['var'])) {
								$tempVars[$paramName] =  $paramData['var'];
							} else {
								$tempVars[$paramName] =  '@' . $paramName . '@';
							}
						}
						$has_with = isset($blockData['with']);
						$params['htmls'][$variantName] = [
							'styles' => $has_with && isset($blockData['with']['styles']) ? $blockData['with']['styles'] : [],
							'scripts' => $has_with && isset($blockData['with']['scripts']) ? $blockData['with']['scripts'] : [],
							'vars' => $tempVars,
							'replaces' => isset($variantParams['replaces']) ? $variantParams['replaces'] : [],
							'php' => 0 < count($phpVars) ? implode(', ', $phpVars) : '',
						];
					}

					$this->Init($srcPath, $params);
					$this->Generate();

					echo '... Ok', PHP_EOL;
				} catch (\Error $e) {
					echo '!!! ОШИБКА: ', $e->getMessage(), PHP_EOL;
				}
				echo PHP_EOL;
			}

			echo 'Генерация окончена!', PHP_EOL;
		} else {
			echo 'Нет блоков, требующих генерации историй Storybook.', PHP_EOL;
		}
	}


	/**
	 * Устанавливает данные истории блока для генератора
	 *
	 * @param  string $srcPath полный путь к папке src
	 * @param  array  $params массив данных frontend блока - html-код, переменные, названия, значения по умолчанию
	 * @param  array  $templates массив путей шаблонов
	 * @return void
	 */
	public function Init($srcPath, $params = [], $templates = [])
	{
		$blockPath = trim($params['block'], '/');
		$_parts = explode('/', $blockPath);
		$this->blockName = end($_parts);
		$this->srcPath = rtrim($srcPath, '/') . '/';
		$this->blockLocal = 'block/' . $blockPath . '/' . $this->blockName;
		$this->blockPath = $this->srcPath . $this->blockLocal;
		$this->storyName = str_replace(['-', '/'], '', ucwords($blockPath, '{-|/}'));
		$this->storyPath = $this->srcPath . 'stories/' . $this->storyName;
		$this->params = $params;

		// Шаблоны для формирования stories
		$_dir = dirname(__FILE__);
		$this->templates = array_merge(
			[
				'stories_template' => $_dir . "/../views/storybook/stories.template",
				'docs_template' => $_dir . "/../views/storybook/docs.template",
				'variants_template' => $_dir . "/../views/storybook/variants.template",
				'controls_template' => $_dir . "/../views/storybook/controls.template",
			],
			$templates,
		);
	}


	/**
	 * Генерирует историю блока в папке stories
	 *
	 * @return void
	 */
	public function Generate()
	{
		// Создание каталога
		if (!file_exists($this->storyPath)) {
			if (!mkdir($this->storyPath, 0775, true)) {
				die ("Не удалось создать каталог stories/{$this->storyName}" . PHP_EOL);
			} else {
				echo static::PREFIX, "создан каталог stories/{$this->storyName}", PHP_EOL;
			}
		}


		$_files = array_filter(glob($this->storyPath . '/*'), function ($entry) {
			$_parts = explode('/', $entry);
			$last = end($_parts);
			return !in_array($last, static::FS_DIRS);
		});

		// Создание html-файлов для story
		$mainVariant = '';
		foreach ($this->params['htmls'] as $variantName => $variantData) {
			$safeVariantName = $this->prepareSafeName($variantName);
			$htmlFile = "{$this->storyPath}/{$this->storyName}.{$safeVariantName}.html";
			$canOverwrite = $this->params['flags']['all_overwrite'];
			if (file_exists($htmlFile)) {
				echo static::PREFIX, static::ESC_RED_START, "html-шаблон {$variantName} блока {$this->blockName} уже существует", static::ESC_FINISH, PHP_EOL;
				$resultVerb = 'переписан';
			} else {
				$canOverwrite = true;
				$resultVerb = 'создан';
			}
			if (null === $canOverwrite) {
				$canOverwrite = in_array($this->getOverwritePermission(basename($htmlFile)), ['y', 'Y']);
			}
			if ($canOverwrite) {
				if (($htmlContent = $this->buildHtml($variantName, $variantData)) &&
					($htmlContent = $this->makeReplaces($variantName, $htmlContent)) &&
					@file_put_contents($htmlFile, $htmlContent)) {
					echo static::PREFIX, static::ESC_GREEN_START, "{$resultVerb} html-шаблон {$variantName} блока {$this->blockName}", static::ESC_FINISH, PHP_EOL;
				} else {
					echo static::PREFIX, static::ESC_RED_START, "не удалось записать html-шаблон {$variantName} блока {$this->blockName}", static::ESC_FINISH, PHP_EOL;
				}
			}
			if ('' === $mainVariant) {
				$mainVariant = $variantName;
			}
			$_files = array_diff($_files, [$htmlFile]);
		}

		// Создание mdx-файла для story
		$mdxFile = "{$this->storyPath}/{$this->storyName}.mdx";
		$canOverwrite = $this->params['flags']['all_overwrite'];
		if (file_exists($mdxFile)) {
			echo static::PREFIX, static::ESC_RED_START, "mdx-файл блока {$this->blockName} уже существует", static::ESC_FINISH, PHP_EOL;
			$resultVerb = 'переписан';
		} else {
			$canOverwrite = true;
			$resultVerb = 'создан';
		}
		if (null === $canOverwrite) {
			$canOverwrite = in_array($this->getOverwritePermission(basename($mdxFile)), ['y', 'Y']);
		}
		if ($canOverwrite) {
			if (($mdxContent = $this->buildMdx($mainVariant)) &&
				@file_put_contents($mdxFile, $mdxContent)) {
				echo static::PREFIX, static::ESC_GREEN_START, "{$resultVerb} mdx-файл блока {$this->blockName}", static::ESC_FINISH, PHP_EOL;
			} else {
				echo static::PREFIX, static::ESC_RED_START, "не удалось записать mdx-файл блока {$this->blockName}", static::ESC_FINISH, PHP_EOL;
			}
		}
		$_files = array_diff($_files, [$mdxFile]);

		// Создание js-файла story
		$storyFile = "{$this->storyPath}/{$this->storyName}.stories.js";
		$canOverwrite = $this->params['flags']['all_overwrite'];
		if (file_exists($storyFile)) {
			echo static::PREFIX, static::ESC_RED_START, "story-файл блока {$this->blockName} уже существует", static::ESC_FINISH, PHP_EOL;
			$resultVerb = 'переписан';
		} else {
			$canOverwrite = true;
			$resultVerb = 'создан';
		}
		if (null === $canOverwrite) {
			$canOverwrite = in_array($this->getOverwritePermission(basename($storyFile)), ['y', 'Y']);
		}
		if ($canOverwrite) {
			if (($storyContent = $this->buildStory()) &&
				@file_put_contents($storyFile, $storyContent)) {
				echo static::PREFIX, static::ESC_GREEN_START, "{$resultVerb} story-файл блока {$this->blockName}", static::ESC_FINISH, PHP_EOL;
			} else {
				echo static::PREFIX, static::ESC_RED_START, "не удалось записать story-файл блока {$this->blockName}", static::ESC_FINISH, PHP_EOL;
			}
		}
		$_files = array_diff($_files, [$storyFile]);

		// Чистим старые файлы
		if (0 < count($_files)) {
			foreach ($_files as $_file) {
				@unlink($_file);
			}
		}
	}


	/**
	 * Формирует содержимое файла с html-кодом блока
	 *
	 * @return string
	 */
	private function buildHtml($variantName = 'Primary', $variantData = [])
	{
		$html = '';

		if ($variantData) {
			if (isset($variantData['styles'])) {
				foreach ($variantData['styles'] as $styleUrl) {
					$html .= '<link type="text/css" rel="stylesheet" href="' . $styleUrl. '"/>' . PHP_EOL;
				}
			}
			if (isset($variantData['scripts'])) {
				foreach ($variantData['scripts'] as $scriptUrl) {
					$html .= '<script type="text/javascript" src="' . $scriptUrl. '"></script>' . PHP_EOL;
				}
			}
			$html .= \TAO::frontend()->renderBlock($this->params['block'], $variantData['vars']);
		}

		return $html;
	}


	/**
	 * Заполняет данными mdx-шаблон раздела документации
	 *
	 * @return string
	 */
	private function buildMdx($variantName = 'Primary')
	{
		$php = '```php' . PHP_EOL . '<?= \TAO::frontend()->renderBlock(\'' . $this->params['block'] .'\'';
		$vars = '';
		if (isset($this->params['htmls'][$variantName]['php']) &&
			$this->params['htmls'][$variantName]['php']) {
			$vars = ', [ ' . $this->params['htmls'][$variantName]['php'] . ' ]';
		} else {
			$varList = [];
			if (isset($this->params['htmls'][$variantName]['vars'])) {
				foreach ($this->params['htmls'][$variantName]['vars'] as $paramName => $paramValue) {
					$q = '\'';
					if (is_array($paramValue)) {
						$value = str_replace('\n', PHP_EOL, var_export($paramValue, true));
						$q = '';
					} else if (!is_string($paramValue)) {
						$value = strval($paramValue);
					} else {
						$_default = '@' . $paramName . '@';
						if ($_default === $paramValue) {
							if (isset($this->params['args'][$variantName][$paramName])) {
								$_value = $this->params['args'][$variantName][$paramName];
								if (is_array($_value)) {
									if (isset($_value['value'])) {
										$value = $_value['value'];
									} else if (isset($_value['default'])) {
										$value = $_value['default'];
									} else {
										$value = '';
									}
								} else if (!is_string($_value)) {
									$value = var_export($_value, true);
									$q = '';
								} else {
									$value = $_value;
								}
							} else {
								$value = '';
							}
						} else {
							$value = $paramValue;
						}
					}
					if ($q) {
						$value = str_replace(["\n", "'"], ['\n', '\\\''], $value);
					}
					$varList[] = '\'' . $paramName . '\' => ' . $q . $value . $q . ',';
				}
			}
			if (0 < count($varList)) {
				$vars = ', [' . PHP_EOL . "\t" . implode(PHP_EOL . "\t", $varList) . PHP_EOL . ']';
			}
		}
		$php .=  $vars . ') ?>' . PHP_EOL . '```';

		$bladeFile = "{$this->blockPath}.blade.php";
		$bladeContent = file_exists($bladeFile) ? '```html' . PHP_EOL . file_get_contents($bladeFile) . PHP_EOL . '```' : '*У блока отсутствует HTML шаблон.*';

		$jsFile = "{$this->blockPath}.js";
		$jsContent = file_exists($jsFile) ? '```js' . PHP_EOL . file_get_contents($jsFile) . PHP_EOL . '```' : '*У блока отсутствуют сценарии.*';

		$scssFile = "{$this->blockPath}.scss";
		$scssContent = file_exists($scssFile) ? '```scss' . PHP_EOL . file_get_contents($scssFile) . PHP_EOL . '```' : '*У блока отсутствуют стили.*';

		$content = file_get_contents($this->templates['docs_template']);

		$content = str_replace("@block_name@", $this->params['title'], $content);
		$content = str_replace("@block@", $this->params['block'], $content);
		$content = str_replace("@php@", $php, $content);
		$content = str_replace("@blade@", $bladeContent, $content);
		$content = str_replace("@js@", $jsContent, $content);
		$content = str_replace("@scss@", $scssContent, $content);

		return $content;
	}


	/**
	 * Заполняет данными js-шаблон сценария истории блока
	 *
	 * @return string
	 */
	private function buildStory()
	{
		$story_section = str_replace('"', '\"', $this->params['section']);
		$story_title = str_replace('"', '\"', $this->params['title']);

		$story_template = file_get_contents($this->templates['stories_template']);

		$story_template = str_replace("@story_section@", $story_section, $story_template);
		$story_template = str_replace("@story_title@", $story_title, $story_template);
		$story_template = str_replace("@block_name@", $this->blockName, $story_template);
		$story_template = str_replace("@docs_name@", $this->storyName, $story_template);

		$variantsList = array_keys($this->params['htmls']);

		// Добавляем импорт html шаблонов
		foreach ($variantsList as $variantName) {
			$safeVariantName = $this->prepareSafeName($variantName);
			$story_template = implode([
				"import html{$safeVariantName} from \"./{$this->storyName}.{$safeVariantName}.html\";" . PHP_EOL,
				$story_template,
			]);
		}

		// Добавляем импорт скриптов, если они есть
		if (file_exists("{$this->blockPath}.js")) {
			$story_template = implode([
				"import \"{$this->blockLocal}.js\"" . PHP_EOL,
				$story_template
			]);
		}

		// Добавляем импорт стилей, если они есть
		if (file_exists("{$this->blockPath}.scss")) {
			$story_template = implode([
				"import \"{$this->blockLocal}.scss\"" . PHP_EOL,
				$story_template
			]);
		}

		// Добавялем параметры
		$parameters = '';
		if (0 < count($this->params['parameters'])) {
			$parameters = json_encode($this->params['parameters'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
			$parameters = trim($parameters, PHP_EOL);
			$parameters = implode(
				PHP_EOL,
				array_filter(array_map(
					function ($str) {
						if (in_array($str, ['{', '}'])) {
							return '';
						}
						// Заменяем пробелы табуляцией
						$m = [];
						preg_match('{^(?P<spaces> +)}', $str, $m);
						$sp = $m['spaces'];
						$tb = trim(str_replace('    ', "\t", $sp), ' ');
						if ((0 < strlen($sp)) &&
							(0 < strlen($tb))) {
							$str = preg_replace('{^ +}', $tb, $str);;
						}

						// Убираем кавычки с названия параметра
						$m = [];
						if (preg_match('{^\t+"(?P<name>[^"-]+)":}', $str, $m)) {
							$str = str_replace('"' . $m['name'] . '":', $m['name'] . ':', $str);
						}

						// Добавляем ещё одну табуляцию
						return "\t" . $str;
					},
					explode(PHP_EOL, $parameters)
				))
			);
			$parameters = ',' . PHP_EOL . $parameters;
		}
		$story_template = str_replace('@parameters@', $parameters, $story_template);

		$base_variant_template = file_get_contents($this->templates['variants_template']);
		$base_controls_template = file_get_contents($this->templates['controls_template']);

		$variants_all = '';
		$controls_all = '';

		foreach ($variantsList as $variantName) {
			$variant_template = $base_variant_template;
			$controls_template = $base_controls_template;

			$replaces = [];

			if (isset($this->params['args'][$variantName])) {
				foreach ($this->params['args'][$variantName] as $_name => $_arg_value) {
					if (!isset($this->params['htmls'][$variantName]['vars'][$_name])) {
						continue;
					}
					$_value = $this->params['htmls'][$variantName]['vars'][$_name];
					if (!is_string($_value)) {
						if (is_array($_value) ||
							is_object($_value)) {
							continue;
						}
						$_value = var_export($_value, true);
					}
					if (isset($this->params['htmls'][$variantName]['replaces']) &&
						($_replaces = $this->params['htmls'][$variantName]['replaces']) &&
						isset($_replaces[$_name])) {

						$_arg = 'args.' . $_name;

						$_empty = var_export($_replaces[$_name]['empty'] ?? '', true);
						$_default = var_export($_replaces[$_name]['default'] ?? '', true);

						$_escape_quotes = (false !== mb_strpos($_replaces[$_name]['code'], '"'));

						$_src = ["\n",		"'",		$_replaces[$_name]['mark']];
						$_dst = ['\n',		'\\\'',		'\' + ' . $_arg . ($_escape_quotes ? '.replaceAll(\'"\', \'&quot;\')' : '') . ' + \''];

						$replaces[] = '\'' . $_value . '\': (() => { return (' . $_empty . ' !== ' . $_arg . ') ? \'' . str_replace(
							$_src,
							$_dst,
							$_replaces[$_name]['code']
						) . '\' : ' . $_default . '; })()';
					} else {
						$replaces[] = '\'' . $_value . '\': (() => { return args.' . $_name . '; })()';
					}
				}
			}

			$safeVariantName = $this->prepareSafeName($variantName);

			$variant_template = str_replace("@variant_name@", $safeVariantName, $variant_template);
			$_indent = PHP_EOL . "\t\t\t";
			$variant_template = str_replace(
				"@replaces@",
				$_indent . implode(',' . $_indent, $replaces) . PHP_EOL . "\t\t",
				$variant_template
			);

			$variants_all = implode([
				$variants_all,
				$variant_template,
			]);

			// Формирование аргументов
			$vars = isset($this->params['args'][$variantName]) ? json_encode($this->params['args'][$variantName], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : '{}';
			$controls_template = str_replace("@args@", $vars, $controls_template);
			$controls_template = str_replace("@name@", $safeVariantName, $controls_template);

			$controls = isset($this->params['controls'][$variantName]) ? json_encode($this->params['controls'][$variantName], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : '{}';
			$controls_template = str_replace("@args_types@", $controls, $controls_template);

			$controls_all = implode([
				$controls_all,
				$controls_template,
			]);
		}

		$story_template = str_replace("@templates@", $variants_all, $story_template);
		$story_template = str_replace("@controls@", $controls_all, $story_template);

		return $story_template;
	}


	/**
	 * Выполняет специальные подмены в html-коде блока
	 *
	 * @param string $variantName название варианта истории
	 * @param string содержимое html-кода блока
	 * @return string
	 */
	private function makeReplaces($variantName, $html)
	{
		if (isset($this->params['htmls'][$variantName]) &&
			($params = $this->params['htmls'][$variantName]) &&
			isset($params['replaces']) &&
			(0 < count($params['replaces']))) {

			foreach ($params['replaces'] as $var => $replace) {
				if (false === mb_strpos($html, $replace['code'])) {
					continue;
				}
				$html = str_replace($replace['code'], $replace['mark'], $html);
			}

		}
		return $html;
	}


	/**
	 * Удаляет опасные символы в названии варианта для использования в именах файлов и переменных js-сценария
	 *
	 * @param string $name название варианта
	 * @return string
	 */
	private function prepareSafeName($name = '')
	{
		return $name ? str_replace([' ', '-'], '_', $name) : '';
	}


	/**
	 * Запрашивает у пользователя подтверждение перезаписи файла
	 * с сохранением вариантов "для всех" в настройках работы генератора
	 *
	 * @param bool|string $fn имя файла (необязательный параметр)
	 * @return string
	 */
	protected function getOverwritePermission($fn = false)
	{
		$answer = '';
		$variants = ['y', 'Y', 'n', 'N'];
		$prompt = '> Переписать файл' . ($fn ? ' ' . $fn : '') . '? (' . implode(', ', $variants). ') ';

		while (!in_array($answer, $variants)) {
			$answer = readline($prompt);
		}

		if ('Y' === $answer) {
			$this->params['flags']['all_overwrite'] = true;
		} else if ('N' === $answer) {
			$this->params['flags']['all_overwrite'] = false;
		}

		return $answer;
	}

}
