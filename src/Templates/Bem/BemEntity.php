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

	/**
	 * Возвращает имя сущности в виде Bem селектора без модификаторов
	 * @return string
	 */
	abstract public function getBemName();

	/**
	 * @param $name - имя сущности
	 * @return $this
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
	 * @deprecated использовать addMod
	 * @param string $name
	 * @param string $value
	 * @return $this
	 */
	public function mod($name, $value = null)
	{
		return $this->addMod($name, $value);
	}

	/**
	 * @param string $name - имя модификатора
	 * @param string|null $value - значение модификатора (опционально)
	 * @return $this
	 */
	public function addMod($name, $value = null)
	{
		if (empty($name)) {
			return $this;
		}

		if (empty($value) && strpos($name, self::VALUE_DIVIDER) !== false) {
			$exploded = explode(self::VALUE_DIVIDER, $name);
			$this->mods[$exploded[0]] = $exploded[1];
		} else {
			$this->mods[$name] = $value;
		}


		return $this;
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
			$mod = $modName;
			if (!empty($modValue)) {
				$mod = $modName . self::VALUE_DIVIDER . $modValue;
			}
			$bemSelector .= " {$this->getBemName()}" . self::MOD_DIVIDER . $mod;
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