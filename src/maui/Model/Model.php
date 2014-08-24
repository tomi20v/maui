<?php

namespace Maui;

abstract class Model implements \IteratorAggregate {

	use \Maui\TraitHasLabel;

	/**
	 * made const so it's easy to get but final
	 */
	const REFERRED = \SchemaManager::REF_AUTO;

	/**
	 * @var array[] initial schema reference. will be unset. To use dynamic schemas override static function __init()
	 */
	protected static $_schema = array();

	/**
	 * @var array|null I will point to data root in heap
	 */
	protected $_attrData = array();

	/**
	 * @var array|null
	 */
	protected $_propData = array();

	/**
	 * @var boolean[] there will be a true for all fields validated after its value was set. [1] is true if all the
	 * 		objects' fields are validated, [0] is true if all set props are validated
	 */
	protected $_isValidated = array();

	/**
	 * @var array[] collection of errors per field
	 */
	protected $_validationErrors = array();

	public function __construct($idOrData=null) {
		$classname = get_called_class();
		if (!\ModelManager::isInited($classname)) {
			static::__init();
		}
		if (is_array($idOrData)) {
			$this->apply($idOrData, true);
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

	/**
	 * I iterate my schema
	 * @return \ArrayIterator|\Traversable
	 */
	public function getIterator() {
		return new \ArrayIterator(\SchemaManager::getSchema($this));
	}

	/**
	 * I return current object cast to another class
	 * @param $classnameOrObject string|mixed
	 */
	public function to($classnameOrObject) {
		throw new \Exception('TBI');
	}

	/**
	 * I load by preset props
	 */
	public function load() {
		$collectionName = $this->getCollectionName();
		$Collection = null;
		$Collection = \Maui::instance()->dbDb()->$collectionName;
		$data = $Collection->findOne($this->getPropData());
		$data = is_null($data) ? array() : $data;
		$this->_attrData = $data;
		$this->_propData = $data;
		return $this;
	}

	public function loadIfNotLoaded() {
		if (empty($this->_attrData)) {
			$this->load();
		}
		return $this;
	}

	public function getPropData() {
		$data = array();
		$Schema = \SchemaManager::getSchema($this);
		foreach($Schema as $eachKey=>$eachVal) {
			if (array_key_exists($eachKey, $this->_propData)) {
				$eachVal = $this->_propData[$eachKey];
				if ($this->hasAttr($eachKey)) {
					$data[$eachKey] = $eachVal;
				}
				elseif ($this->hasRelative($eachKey)) {
					if (is_object($eachVal) && !($eachVal instanceof \MongoId)) {
						$Rel = $Schema->attr($eachKey);
						$eachVal = $Rel->getObjectData($eachVal);
					}
					$data[$eachKey] = $eachVal;
				}
			}
		}
		return $data;
	}

	/**
	 * @return string
	 * @extendMe
	 */
	public function getCollectionName() {
		return str_replace('\\', '_', trim(get_class($this), '\\'));
	}

	public function find($by) {
		throw new \Exception('TBI'); ;
	}

	public function save($fields=null, $deep=false) {
		throw new \Exception('TBI'); ;
	}

	/**
	 * I return if an attribute exists in my schema
	 *
	 * @param string $key
	 * @return bool
	 */
	public function hasAttr($key) {
		return \SchemaManager::getSchema($this)->hasAttr($key);
	}

	/**
	 * I return an attribute value. Attr value should not be set by app code, so just getter.
	 *
	 * @param $key
	 * @return mixed|null
	 */
	public function attr($key) {
		if (!$this->hasAttr($key)) {
			throw new \Exception('attr ' . $key . ' does not exists');
		}
		return isset($this->_attrData[$key]) ? $this->_attrData[$key] : null;
	}

	/**
	 * I return or set a property
	 *
	 * @param $key
	 * @param null $value
	 * @return mixed|null
	 * @throws \Exception
	 */
	public function prop($key, $value=null) {
		if (!$this->hasAttr($key)) {
			throw new \Exception(echon($key) . ' / ' . echon($value));
		}
		elseif (count(func_get_args()) == 1) {
			return array_key_exists($key, $this->_propData)
				? $this->_propData[$key]
				: null;
		}
		else {
			return $this->_apply($key, $value);
		}
	}

	public function apply($data, $overwrite=false) {
		if (!is_array($data)) {
			throw new \Exception(echon($data));
		}
		elseif($overwrite) {
			$this->_attrData = $data;
			$this->_propData = $data;
		}
		else {
			throw new \Exception('TBI');
		}
		return $this;
	}

	protected function _apply($key, $value) {
		$wasNull = is_null($value);
		$Attr = \SchemaManager::getSchema($this)->attr($key);
		$value = $Attr->apply($value);
		if (is_null($value) && !$wasNull) {
			return null;
		}
		$this->_propData[$key] = $value;
		$this->_isValidated[false] = false;
		$this->_isValidated[true] = false;
		$this->_isValidated[$key] = false;
		return $value;
	}

	/**
	 * I return if I have relative referenced by $attr field
	 * @param string $attr
	 * @return bool
	 */
	public function hasRelative($attr) {
		return \SchemaManager::getSchema($this)->hasRelative($attr);
	}

	/**
	 * I return or set relative on field $attr
	 * @param $attr
	 * @param $this|mixed $value
	 */
	public function relative($attr, $value=null) {

		if (count(func_get_args()) == 1) {
			 $ret = null;
			if (array_key_exists($attr, $this->_propData)) {
				$ret = $this->_propData[$attr];
				if (is_object($ret) && !($ret instanceof \MongoId));
				else {
					$Rel = \SchemaManager::getSchema($this)->attr($attr);
					$ret = $Rel->getReferredObject($this->_propData[$attr]);
					$this->_propData[$attr] = $ret;
				}
			}
		}
		else {
			throw new \Exception('TBI'); ;
		}
		return $ret;
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
				$Schema = \SchemaManager::getSchema($this);
				foreach ($Schema as $eachKey=>$eachEl) {
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
					$errors = \SchemaManager::getSchema($this)->$eachKey->getErrors($eachVal);
					if (!empty($errors)) {
						$this->_validationErrors[$eachKey] = $errors;
					}
					$this->_isValidated[$eachKey] = true;
				}
				if ($_key === false) {
					$this->_isValidated[false] = true;
				}
				if ($key == array_keys(\SchemaManager::getSchema($this)->attrs())) {
					$this->_isValidated[true] = true;
				}
				$errors = array_intersect_key($this->_validationErrors, $key);
				return empty($errors);
				break;
			case $this->hasAttr($key):
			case $this->hasRelative($key):
				unset($this->_validationErrors[$key]);
				$val = $this->$key;
				$errors = \SchemaManager::getSchema($this)->$key->getErrors($val);
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
