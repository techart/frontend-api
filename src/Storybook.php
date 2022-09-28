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

	private $blockName;
	private $srcPath;
	private $blockLocal;
	private $blockPath;
	private $storyName;
	private $storyPath;
	private $params;
	private $templates;


	/**
	 * Возвращает массив путей к файлам storybook.json из переданной директории
	 *
	 * @param  string $dir путь директории, в которой искать
	 * @return array
	 */
	static public function scanStorybookJson($dir, $only_blocks = []) {
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
	 * __construct
	 *
	 * @param  string $srcPath полный путь к папке src
	 * @param  array  $params массив данных frontend блока - html-код, переменные, названия, значения по умолчанию
	 * @param  array  $templates массив путей шаблонов
	 * @return void
	 */
	public function __construct($srcPath, $params = [], $templates = []) {
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
	 * Генерирует story блока в папке stories
	 *
	 * @return void
	 */
	public function Generate() {
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
			$htmlFile = "{$this->storyPath}/{$this->storyName}.{$variantName}.html";
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
	 * Заполняет mdx шаблон данными
	 *
	 * @return string
	 */
	private function buildMdx($variantName = 'Primary') {
		$php = '```php' . PHP_EOL . '<?= \TAO::frontend()->renderBlock(\'' . $this->params['block'] .'\'';
		$vars = '';
		if (isset($this->params['htmls'][$variantName]['php']) &&
			$this->params['htmls'][$variantName]['php']) {
			$vars = ', ' . $this->params['htmls'][$variantName]['php'];
		} else {
			$varList = [];
			if (isset($this->params['htmls'][$variantName]['vars'])) {
				foreach ($this->params['htmls'][$variantName]['vars'] as $paramName => $paramValue) {
					$q = '"';
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
						$value = str_replace(["\n", '"'], ['\n', '\"'], $value);
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
		$scssContent = file_exists($scssFile) ? '```css' . PHP_EOL . file_get_contents($scssFile) . PHP_EOL . '```' : '*У блока отсутствуют стили.*';

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
	 * Заполняет js шаблон данными
	 *
	 * @return string
	 */
	private function buildStory() {
		$story_section = $this->params['section'];
		$story_title = $this->params['title'];

		$story_template = file_get_contents($this->templates['stories_template']);

		$story_template = str_replace("@story_section@", $story_section, $story_template);
		$story_template = str_replace("@story_title@", $story_title, $story_template);
		$story_template = str_replace("@block_name@", $this->blockName, $story_template);
		$story_template = str_replace("@docs_name@", $this->storyName, $story_template);

		$variantsList = array_keys($this->params['htmls']);

		// Добавляем импорт html шаблонов
		foreach ($variantsList as $variantName) {
			$story_template = implode([
				"import html{$variantName} from \"./{$this->storyName}.{$variantName}.html\";" . PHP_EOL,
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
			foreach ($this->params['args'][$variantName] as $_name => $_arg_value) {
				if (!isset($this->params['htmls'][$variantName]['vars'][$_name])) {
					continue;
				}
				$_value = $this->params['htmls'][$variantName]['vars'][$_name];
				if (!is_string($_value)) {
					continue;
				}
				$replaces[] = '\'' . $_value . '\': (() => { return args.' . $_name . '; })()';
			}

			$variant_template = str_replace("@variant_name@", $variantName, $variant_template);
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
			$controls_template = str_replace("@name@", $variantName, $controls_template);

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
