<?php

namespace Techart\Frontend\Templates\Bem;

/**
 * Class BlockEntity
 * @package Techart\Frontend\Templates\Bem
 *
 * Класс реализует базовый функционал объекта для работы с bem внутри twig шаблонов
 */
abstract class BemEntity
{
	const MOD_DIVIDER = '--';
	const VALUE_DIVIDER = '_';

	/** @var  string имя сущности */
	protected $name;
	/** @var array список модификаторов */
	protected $mods = array();
	/** @var string $deprecatedMod */
	protected $deprecatedMod;

	/**
	 * Возвращает имя сущности в виде Bem селектора без модификаторов
	 * @return string
	 */
	abstract public function getBemName();

	/**
	 * @param $name - имя сущности
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @deprecated использовать toString
	 * @return string
	 */
	public function cls()
	{
		return $this->toString();
	}

	/**
	 * @param string $name - имя модификатора
	 * @param mixed $value = true - значение модификатора (опционально)
	 * @return $this
	 */
	public function mod($name, $value = true)
	{
		if (empty($name)) {
			return $this;
		}

		if (preg_match('/[\s]+/', $name)) {
			$name = preg_split('/[\s,]+/', $name);
		}

		if (is_array($name)) {
			if ($this->is_assoc($name)) {
				foreach ($name as $modName => $modValue) {
					$this->mod($modName, $modValue);
				}
			} else {
				foreach ($name as $modName) {
					$this->mod($modName);
				}
			}
		} else if (strpos($name, self::VALUE_DIVIDER) !== false) {
			$exploded = explode(self::VALUE_DIVIDER, $name);
			$this->mod($exploded[0], $exploded[1]);
		} else {
			$this->mods[$name] = $value;
		}

		return $this;
	}

	protected function is_assoc($arr) {
		if (!is_array($arr) || array() === $arr) {
			return false;
		}

		return array_keys($arr) !== range(0, count($arr) - 1);
	}

	/**
	 * @deprecated использовать mod
	 * @param string $val
	 * @return $this
	 */
	public function val($val)
	{
		return $this->addMod($this->deprecatedMod, $val);
	}

	/**
	 * @alias mod
	 * @param string $name - имя модификатора
	 * @param mixed $value = true - значение модификатора (опционально)
	 * @return $this
	 */
	public function addMod($name, $value = true)
	{
		return $this->mod($name, $value);
	}

	/**
	 * @param string $name - имя модификатора
	 * @return $this
	 */
	public function removeMod($name)
	{
		unset($this->mods[$name]);

		return $this;
	}

	/**
	 * Удаляет все модификаторы
	 * @return $this
	 */
	public function clearMods()
	{
		$this->mods = array();

		return $this;
	}

	/**
	 * @return string
	 */
	public function toString()
	{
		$bemSelector = $this->getBemName();
		foreach ($this->mods as $modName => $modValue) {
			$mod = null;
			if ($modValue === false) {
				$mod = '';
			} else if ($modValue === true || $modValue === '' || is_null($modValue)) {
				$mod = $modName;
			} else {
				$mod = $modName . self::VALUE_DIVIDER . $modValue;
			}

			if ($mod !== '') {
				$bemSelector .= " {$this->getBemName()}" . self::MOD_DIVIDER . $mod;
			}
		}

		return $bemSelector;
	}

	/**
	 * Транслирует вызов на toString (нужен в шаблоне твига)
	 * @return string
	 */
	public function __toString()
	{
		return $this->toString();
	}
}
