<?php

namespace Maui;

use Maui\SchemaManager;

abstract class Model {

	use \Maui\TraitHasLabel;

	/**
	 * made const so it's easy to get but final
	 */
	const REFERRED = SchemaManager::REF_AUTO;

	/**
	 * @var mixed I will point to data root in heap
	 */
	protected $_dataPtr;

	/**
	 * @return \Schema
	 */
	public static function getSchema() {
		return \SchemaManager::getSchema(get_called_class());
	}

	public function __construct($id=null) {
		$classname = get_called_class();
		if (!\ModelManager::isInited($classname)) {
			static::__init();
		}
		if (is_array($id)) {
			$this->apply($id);
		}
		elseif (is_object($id) && $id instanceof \Model) {
			$this->apply($id);
		}
		elseif (is_string($id)) {
			$this->_id = $id;
		}
		// @todo register the model here if already has ID. also write setID() which registers the object
	}

	public function __get($attr) {
		switch(true) {
			case $attr == 'Schema':
				return \SchemaManager::getSchema(get_called_class());
			case $this->hasAttr($attr):
				return $this->attr($attr);
			case $this->hasRelative($attr):
				return $this->relative($attr);
			default:
				throw new \Exception();
		}
	}

	public function __set($attr, $val) {
		switch(true) {
			case $attr == 'Schema':
				throw new \Exception('schema cannot be written directly');
			case $this->hasAttr($attr):
				return $this->attr($attr, $val);
			case $this->hasRelative($attr):
				return $this->relative($attr, $val);
			default:
				print_r($attr); print_r($val);
				throw new \Exception();
		}
	}

	/**
	 * I must be called before anything (__construct() does it)
	 */
	public static function __init() {
		$classname = get_called_class();
		\SchemaManager::registerSchema($classname, static::$_schema);
		\ModelManager::registerInited($classname);
	}

	/**
	 * I return current object cast to another class
	 * @param $classnameOrObject string|mixed
	 */
	public function to($classnameOrObject) {
		die('TBI');
	}

	public function find($by) {
		die('TBI');
	}

	public function save() {
		die('TBI');
	}

	/**
	 * I return if an attribute exists in my schema
	 * @param string $attr
	 * @return bool
	 */
	public function hasAttr($attr) {
		return static::getSchema()->hasAttr($attr);
	}

	/**
	 * I return or set the value of an attr
	 * @param $attr
	 * @param $this|mixed
	 */
	public function attr($attr, $value=null) {
		die('TBI');
		if (!$this->hasAttr($attr)) {
			throw new \Exception('attr ' . $attr . ' does not exists');
		}
		if (count(func_get_args()) == 1) {

		}
		else {

		}
	}

	/**
	 * I return if I have relative referenced by $attr field
	 * @param string $attr
	 * @return bool
	 */
	public function hasRelative($attr) {
		return static::getSchema()->hasRelative($attr);
	}

	/**
	 * I return if I have
	 * @param $classname string|mixed
	 */
	public function hasRelativeOf($classname) {
		die('TBI');
	}

	/**
	 * I return or set relative on field $attr
	 * @param $attr
	 * @param $this|mixed $value
	 */
	public function relative($attr, $value=null) {
		die('TBI');
		if (count(func_get_args()) == 1) {

		}
		else {

		}
	}

}
