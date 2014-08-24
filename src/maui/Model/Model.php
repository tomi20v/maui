<?php

namespace Maui;

use Maui\SchemaManager;

abstract class Model implements \IteratorAggregate {

	use \Maui\TraitHasLabel;

	/**
	 * made const so it's easy to get but final
	 */
	const REFERRED = SchemaManager::REF_AUTO;

	protected static $_schema = array();

	/**
	 * @var array|null I will point to data root in heap
	 */
	protected $_attrData = array();

	/**
	 * @var array|null
	 */
	protected $_propData = array();

	protected $_isValidated;

	protected $_validationErrors = array();

	public function __construct($idOrData=null) {
		$classname = get_called_class();
		if (!\ModelManager::isInited($classname)) {
			static::__init();
		}
		if (is_array($idOrData)) {
			$this->apply($idOrData);
		}
		elseif (is_object($idOrData) && $idOrData instanceof \Model) {
			$this->apply($idOrData);
		}
		elseif (is_string($idOrData)) {
			$this->_id = new \MongoId($idOrData);
		}
		// @todo register the model here if already has ID. also write setID() which registers the object
	}

	public function __get($attr) {
		switch(true) {
			case $attr == 'Schema':
				return \SchemaManager::getSchema(get_called_class());
			case $this->hasAttr($attr):
				return $this->prop($attr);
			case $this->hasRelative($attr):
				return $this->relative($attr);
			default:
				throw new \Exception(echon($attr));
		}
	}

	public function __set($attr, $val) {
		switch(true) {
			case $attr == 'Schema':
				throw new \Exception('schema cannot be written directly');
			case $this->hasAttr($attr):
				return $this->prop($attr, $val);
			case $this->hasRelative($attr):
				return $this->relative($attr, $val);
			default:
				throw new \Exception(echon($attr) . echon($val));
		}
	}

	/**
	 * I must be called before anything (__construct() does it)
	 */
	public static function __init() {
		$classname = get_called_class();
		\SchemaManager::registerSchema(
			$classname,
			\SchemaManager::ensureHasId(static::$_schema)
		);
		static::$_schema = null;
		\ModelManager::registerInited($classname);
	}

	public function getIterator() {
		return new \ArrayIterator(\SchemaManager::getSchema(get_called_class()));
	}

	/**
	 * I return current object cast to another class
	 * @param $classnameOrObject string|mixed
	 */
	public function to($classnameOrObject) {
		throw new \Exception('TBI'); ;
	}

	public function find($by) {
		throw new \Exception('TBI'); ;
	}

	public function save() {
		throw new \Exception('TBI'); ;
	}

	/**
	 * I return if an attribute exists in my schema
	 * @param string $attr
	 * @return bool
	 */
	public function hasAttr($attr) {
		return $this->Schema->hasAttr($attr);
	}

	/**
	 * I return an attribute value. Attr value should not be set by app code, so just getter.
	 * @param $attr
	 * @return mixed|null
	 */
	public function attr($attr) {
		if (!$this->hasAttr($attr)) {
			throw new \Exception('attr ' . $attr . ' does not exists');
		}
		return isset($this->_attrData[$attr]) ? $this->_attrData[$attr] : null;
	}

	/**
	 * I return or set a property
	 * @param $attr
	 * @param null $value
	 * @return mixed|null
	 * @throws \Exception
	 */
	public function prop($attr, $value=null) {
		if (!$this->hasAttr($attr)) {
			throw new \Exception(echon($attr) . ' / ' . echon($value));
		}
		elseif (count(func_get_args()) == 1) {
			return array_key_exists($attr, $this->_propData)
				? $this->_propData[$attr]
				: null;
		}
		else {
			return $this->_apply($attr, $value);
		}
	}

	protected function _apply($key, $value) {
		$wasNull = is_null($value);
		$Attr = $this->Schema->attr($key);
		$value = $Attr->apply($value);
		if (is_null($value) && !$wasNull) {
			return null;
		}
		$this->_propData[$key] = $value;
		$this->_isValidated = false;
		return $value;
	}

	/**
	 * I return if I have relative referenced by $attr field
	 * @param string $attr
	 * @return bool
	 */
	public function hasRelative($attr) {
		return $this->Schema->hasRelative($attr);
	}

	/**
	 * I return if I have
	 * @param $classname string|mixed
	 */
	public function hasRelativeOf($classname) {
		throw new \Exception('TBI'); ;
	}

	/**
	 * I return or set relative on field $attr
	 * @param $attr
	 * @param $this|mixed $value
	 */
	public function relative($attr, $value=null) {
		if (count(func_get_args()) == 1) {
			return array_key_exists($attr, $this->_propData)
				? $this->_propData[$attr]
				: null;
		}
		else {
			throw new \Exception('TBI'); ;
		}
	}

	/**
	 * @todo make it cached by $this->_isValid again!?
	 * @param bool $key
	 * @return bool
	 */
	public function isValid($key=true) {
		$errors = $this->getErrors($key);
		return empty($errors);
	}

	public function validate($key=true) {
		$_key = $key;
		switch(true) {
			case $key === true:
				$this->_isValidated = array(true=>false, false=>false);
				$this->_validationErrors = array();
				foreach ($this->Schema as $eachKey=>$eachEl) {
//					echop('validating: ' . $eachKey);
					$eachVal = $this->$eachKey;
//					echop($eachVal);
					$errors = $eachEl->getErrors($eachVal);
					if (!empty($errors)) {
						$this->_validationErrors[$eachKey] = $errors;
					}
					$this->_isValidated[$eachKey] = true;
				}
				$this->_isValidated[true] = true;
				$this->_isValidated[false] = true;
				return empty($this->_validationErrors);
				break;
			case $key === false:
				$key = array_keys($this->_propData);
				$this->_isValidated[false] = false;
				// fallthrough
			case is_array($key):
				foreach ($key as $eachKey) {
					unset($this->_validationErrors[$eachKey]);
					$eachVal = $this->$eachKey;
					$errors = $this->Schema->$eachKey->getErrors($eachVal);
					if (!empty($errors)) {
						$this->_validationErrors[$eachKey] = $errors;
					}
					$this->_isValidated[$eachKey] = true;
				}
				if ($_key === false) {
					$this->_isValidated[false] = true;
				}
				if ($key == array_keys($this->Schema->attrs())) {
					$this->_isValidated[true] = true;
				}
				$errors = array_intersect_key($this->_validationErrors, $key);
				return empty($errors);
				break;
			case $this->hasAttr($key):
			case $this->hasRelative($key):
				unset($this->_validationErrors[$key]);
				$val = $this->$key;
				$errors = $this->Schema->$key->getErrors($val);
				if (!empty($errors)) {
					$this->_validationErrors[$key] = $errors;
				};
				return empty($errors);
			default:
				throw new \Exception(echon($key));
		}
	}

	public function getErrors($key=true) {
		switch(true) {
			case $key === true:
				if (!$this->_isValidated[true]) {
					$this->validate(true);
				}
				return $this->_validationErrors;
			case $key === false:
				$key = array_keys($this->_propData);
				// fallthrough
			case is_array($key):
				$needValidation = false;
				if ($this->_isValidated[true]);
				else {
					foreach ($key as $eachKey) {
						if (!isset($this->_isValidated[$eachKey]) || !$this->_isValidated[$eachKey]) {
							$this->validate($key);
							break;
						}
					}
				}
				return array_intersect_key($this->_validationErrors, $key);
			case $this->hasAttr($key):
			case $this->hasRelative($key):
				if (!isset($this->_isValidated[$key]) || !$this->_isValidated[$key]) {
					$this->validate($key);
				}
				return isset($this->_validationErrors[$key]) ? $this->_validationErrors[$key] : null;
			default:
				throw new \Exception(echon($key));
		}
	}

}
