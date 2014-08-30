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
	protected $_originalData = array();

	/**
	 * @var array|null
	 */
	protected $_data = array();

	/**
	 * @var boolean[] there will be a true for all fields validated after its value was set. [1] is true if all the
	 * 		objects' fields are validated, [0] is true if all set props are validated
	 */
	protected $_isValidated = array();

	/**
	 * @var array[] collection of errors per field
	 */
	protected $_validationErrors = array();

	////////////////////////////////////////////////////////////////////////////////
	// basic & magic
	////////////////////////////////////////////////////////////////////////////////

	public function __construct($idOrData=null, $dataIsOriginal=false) {
		$classname = get_called_class();
		if (!\ModelManager::isInited($classname)) {
			static::__init();
		}
		if (is_array($idOrData)) {
			$this->apply($idOrData, $dataIsOriginal);
		}
		elseif (is_object($idOrData) && $idOrData instanceof \MongoId) {
			$this->_id = $idOrData;
		}
		elseif (is_string($idOrData)) {
			$this->_id = new \MongoId($idOrData);
		}
	}

	public function __get($key) {
		if ($this->hasField($key)) {
			return $this->field($key);
		}
		throw new \Exception(echon($key));
	}

	public function __set($key, $val) {
		if ($this->hasField($key)) {
			return $this->field($key, $val);
		}
		throw new \Exception(echon($key) . echon($val));
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
		return new \ArrayIterator($this->_getSchema());
	}

	protected function _getSchema() {
		return \SchemaManager::getSchema($this);
	}

	////////////////////////////////////////////////////////////////////////////////
	//	CRUD etc
	////////////////////////////////////////////////////////////////////////////////

	protected function _getDbCollection() {
		$collectionClassname = $this->getCollectionClassName();
		$collectionName = $collectionClassname::getDbCollectionName();
		return \Maui::instance()->dbDb()->$collectionName;
	}

	/**
	 * I return empty collection for this class
	 * @param null $data
	 * @return mixed
	 * @extendMe eg. in case reusing a collection class for multiple models
	 */
	public static function getCollection($data=null) {
		$collectionClassname = static::getCollectionClassName();
		if (class_exists($collectionClassname)) {
			$ret = new $collectionClassname($data);
		}
		else {
			$ret = new \Collection($data, get_called_class());
		}
		return $ret;
	}

	/**
	 * This shall be the name of the collection in DB as well as the collection classname
	 * @return string
	 * @extendMe you can reuse collections for different models with this
	 */
	public static function getCollectionClassName() {
		return ucfirst(get_called_class()) . 'Collection';
	}

	/**
	 * I load by modified attributes
	 */
	public function load() {
		$Collection = $this->_getDbCollection();
		$data = $Collection->findOne($this->getData(false));
		$data = is_null($data) ? array() : $data;
		$this->_originalData = $data;
		$this->_data = array();
		return $this;
	}

	/**
	 * I load data if hasn't been loaded yet
	 * @return $this
	 */
	public function ensureLoaded() {
		if (empty($this->_originalData)) {
			$this->load();
		}
		return $this;
	}

	public function find($by) {
		throw new \Exception('TBI'); ;
	}

	/**
	 * @param bool|string[] $fieldsOrDeepsave
	 * 	true - do deep save (trigger save on all related objects). No save if no change
	 *  false - only save this objects' updated fields. No save if no change
	 *  string[] - saves the given fields, regardless if they have been changed
	 * @param \Model[] this param is to be passed recursively and holds all objects' ID which's
	 * 		save has been triggered already. This avoids infinite recursion in case of cyclic references
	 * @return $this|null - to be clarified
	 * @throws \Exception
	 */
	public function save($fieldsOrDeepsave=true, &$excludedObjectIds=array()) {
		$DbCollection = $this->_getDbCollection();
		// do deep validation and save
		if ($fieldsOrDeepsave === true) {
			// first save referenced relatives
			// @todo - implement deep save...
//			$relatives = $this->_getReferencedRelatives();
//			foreach ($relatives as $EachRelative) {
//				$EachRelative->save(true, $excludedObjectIds);
//			}
			// save only if there is actual data
			if (!empty($this->_data)) {
				$data = $this->getData(true);
				$result = $DbCollection->save(
					$data
				);
				if (isset($result['ok']) && $result['ok']) {
					$this->_originalData = $data;
					// @todo save already created relative objects here
					$this->_data = array();
				}
			}
			return $result;
		}
		elseif ($fieldsOrDeepsave === false) {
			if (empty($this->_data)) {
				return $this;
			}
			if (!$this->validate(false)) {
				return null;
			}
			$data = $this->getData(false);
			if ($this->_id) {
				$result = $DbCollection->update(
					array('_id' => $this->_id),
					array('$set' => $data)
				);
			}
			else {
				$result = $DbCollection->save(
					$data
				);
			}
			if (isset($result['ok']) && $result['ok']) {
				foreach ($data as $eachKey=>$eachVal) {
					$this->_originalData[$eachKey] = $eachVal;
					unset($this->_data[$eachKey]);
				}
			}
			// I might want to save return value?
			return $result;
		}
		elseif (is_array($fieldsOrDeepsave)) {
			throw new \Exception('TBI');
		}

	}

	////////////////////////////////////////////////////////////////////////////////
	// setters getters, data related
	////////////////////////////////////////////////////////////////////////////////

	/**
	 * I match two arrays or objects or one each and check if $modelData contains $data recursively.
	 * 		Note the check is done by converting both data to array and inspecting each key-val.
	 * @param $modelData
	 * @param $data
	 * @return bool|null
	 */
	public static function match($modelData, $data) {
		if ($modelData instanceof \Model) {
			$modelData = $modelData->getData();
		}
		if ($data instanceof \Model) {
			$data = $data->getData();
		}
		if (!is_array($modelData) || !is_array($data)) {
			return null;
		}
		if (count(array_diff_key($data, $modelData))) {
			return false;
		}
		foreach ($data as $eachKey=>$eachVal) {
			$modelVal = $modelData[$eachKey];
			if (is_array($modelVal) || ($modelVal instanceof \Model) ||
				is_array($eachVal) || ($eachVal instanceof \Model)) {
				if (!static::match($modelVal, $eachVal)) {
					return false;
				}
			}
			else {
				if ($modelVal != $eachVal) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * I wrap data getter methods, see code for examples
	 *
	 * @param null|string|array $keyOrData
	 * @param null|mixed|boolean $valOrDataIsOriginal
	 * @return array|mixed|null
	 */
	public function data($keyOrData=null, $valOrDataIsOriginal=null) {
	 	$num = func_num_args();
		// ->data() returns actual data array
		if (is_null($keyOrData) && is_null($valOrDataIsOriginal)) {
			return $this->getData();
		}
		// ->data($key) returns value of field on key $key
		elseif (($num == 1) && is_string($keyOrData)) {
			return $this->$keyOrData;
		}
		// ->data(array(...)) sets data
		elseif (($num == 1) && is_array($keyOrData)) {
			$this->apply($keyOrData);
		}
		// ->data(array(...), true) sets originalData and clears data
		elseif (($num == 2) && is_array($keyOrData) && ($valOrDataIsOriginal===true)) {
			$this->apply($keyOrData, true);
		}
		// ->data($key, $val) sets field $key to $val (can be field or relative)
		elseif (($num == 2) && is_string($keyOrData)) {
			$this->$keyOrData = $valOrDataIsOriginal;
		}
	}

	/**
	 * I return current data representation
	 * @param bool $allOrChanged if true, all data is returned, if false, just the changed fields plus ID, otherwise nothing...
	 * @param null|string[] send an array of fieldnames to filter data
	 * @return array
	 */
	public function getData($allOrChanged = true, $returnFields = null) {
		$data = array();
		$Schema = $this->_getSchema();
		foreach($Schema as $eachKey=>$EachField) {
			if (is_array($returnFields) && isset($returnFields[$eachKey])) {
				continue;
			}
			// if I have this property set
			if ((($allOrChanged === true) && (array_key_exists($eachKey, $this->_originalData)||array_key_exists($eachKey, $this->_data)) ||
				(($allOrChanged === false) && array_key_exists($eachKey, $this->_data)))) {
				$eachVal = $this->field($eachKey);
				// if it's an attribute, get applied value
				if ($this->hasAttr($eachKey)) {
					$data[$eachKey] = $EachField->apply($eachVal);
				}
				// if a relative, get its data through the relation object
				elseif ($this->hasRelative($eachKey)) {
					if (is_array($eachVal) && empty($eachVal['_id'])) {
						$data[$eachKey] = $eachVal;
					}
					elseif (is_object($eachVal) && empty($eachVal->_id)) {
						$data[$eachKey] = $eachVal->getData($allOrChanged);
					}

				}
			}
		}
		return $data;
	}

	/**
	 * I return if an attribute exists in my schema
	 *
	 * @param string $key
	 * @return bool
	 */
	public function hasField($key) {
		return $this->_getSchema()->hasField($key);
	}

	/**
	 * I set or get a field. Indeed I just wrap attr() and relative() methods
	 * @param $key field name to set or get
	 * @param null $val if present, field will be set, otherwise just returned
	 * @return $this|Model|null I return $this on set, or mixed for get. Note:
	 * 	field($key) returns actual data, with fallback to return original data
	 * @throws \Exception
	 */
	public function field($key, $val=null) {
		if (!$this->hasField($key)) {
			throw new \Exception('field ' . $key . ' does not exists');
		}
		if (func_num_args() == 1) {
			if ($this->hasAttr($key)) {
				return $this->_attr($key);
			}
			elseif ($this->hasRelative($key)) {
				return $this->_relative($key);
			}
		}
		else {
			if ($this->hasAttr($key)) {
				return $this->_attr($key, $val);
			}
			elseif ($this->hasRelative($key)) {
				return $this->_relative($key, $val);
			}
		}
	}

	/**
	 * I return original data (as loaded or saved) or a field of it
	 * @param null $key
	 * @return array|null
	 */
	public function originalField($key=null) {
		if (func_num_args() == 0) {
			return $this->_originalData;
		}
		elseif ($this->hasAttr($key)) {
			return isset($this->_originalData[$key])
				? $this->_originalData[$key]
				: null;
		}
		elseif ($this->hasRelative($key)) {
			throw new \Exception('TBI');
		}
		throw new \Exception($key);
	}

	/**
	 * I return if I have an attribute called $key
	 * @param $key
	 * @return bool
	 */
	public function hasAttr($key) {
		return $this->_getSchema()->hasAttr($key);
	}

	/**
	 * I return or set value on field $key
	 * @param $key
	 * @param null $val
	 * @return $this|null
	 * @throws \Exception
	 */
	protected function _attr($key, $val=null) {
		if (func_num_args() == 1) {
			if (array_key_exists($key, $this->_data)) {
				return $this->_data[$key];
			}
			elseif (array_key_exists($key, $this->_originalData)) {
				return $this->_originalData[$key];
			}
			return null;
		}
		else {
			// note that I do not perform apply() here
			$this->_data[$key] = $val;
			return $this;
		}
		throw new \Exception('TBI');
	}

	/**
	 * I return if I have relative(s) called $key
	 * @param $key
	 * @return bool
	 */
	public function hasRelative($key) {
		return $this->_getSchema()->hasRelative($key);
	}

	/**
	 * I return or set relative on field $field
	 *
	 * @param $key
	 * @param $this|mixed $value
	 */
	public function _relative($key, $val=null) {
		$ret = null;
		if (count(func_get_args()) == 1) {
			if (!array_key_exists($key, $this->_data) && array_key_exists($key, $this->_originalData)) {
				$this->_data[$key] = $this->_originalData[$key];
			}
			if (array_key_exists($key, $this->_data)) {
				$ret = $this->_data[$key];
				if (is_object($ret) && !($ret instanceof \MongoId));
				else {
					$Rel = $this->_getSchema()->field($key);
					$ret = $Rel->getReferredObject($this->_data[$key]);
					$this->_data[$key] = $ret;
				}
			}
		}
		else {
			if ($val instanceof \Model) {
				$Rel = $this->_getSchema()->field($key);
				$classname = $Rel->getObjectClassname();
				if (!$val instanceof $classname) {
					throw new \Exception(echon($key) . ' / ' . echon($val));
				}
			}
			elseif ($val instanceof \Collection) {
				$Rel = $this->_getSchema()->field($key);
				$classname = $Rel->getObjectClassname();
				$collectionClassname = $classname::getCollectionName();
				if (!$val instanceof $collectionClassname) {
					throw new \Exception(echon($key) . ' / ' . echon($val));
				}
			}
			$this->_data[$key] = $val;
			$ret = $this;
		}
		return $ret;
	}

	/**
	 * I set or merge data
	 * @param miced[] $data data to use
	 * @param bool $overwrite if true, I set originalData. Otherwise, I add $data to
	 * 		current $_data. NOTE collections are overwritten this way (maybe fix this?)
	 * @return $this
	 * @throws \Exception
	 */
	public function apply($data, $overwrite=false) {
		if (!is_array($data)) {
			throw new \Exception(echon($data));
		}
		elseif($overwrite) {
			$this->_originalData = $data;
			$this->_data = array();
		}
		else {
			foreach ($data as $eachKey=>$eachVal) {
				$this->field($eachKey, $eachVal);
			}
		}
		return $this;
	}

	////////////////////////////////////////////////////////////////////////////////
	// validation
	////////////////////////////////////////////////////////////////////////////////

	/**
	 * @todo make it cached by $this->_isValid again!?
	 * @param bool $key
	 * @return bool
	 */
	public function isValid($key=true) {
		$errors = $this->getErrors($key);
		return empty($errors);
	}

	/**
	 * @param bool|string|string[] $key
	 * 	true   - validate all values in model context
	 *  false  - validate all values formally
	 *  string - validate one field formally
	 *  string[] - validate some fields in model context
	 * @return bool
	 * @throws \Exception
	 */
	public function validate($key=true) {
		switch(true) {
			case $key === true:
			case $key === false:
				$this->_isValidated = array(true=>false, false=>false);
				$this->_validationErrors = array();
				$Schema = $this->_getSchema();
				foreach ($Schema as $eachKey=>$EachField) {
					if (!$key && !array_key_exists($eachKey, $this->_data)) {
						continue;
					}
					$eachVal = $this->$eachKey;
					$errors = $EachField->getErrors($eachVal, $key ? $this : null);
					if (!empty($errors)) {
						$this->_validationErrors[$eachKey] = $errors;
					}
					$this->_isValidated[$eachKey] = $key;
				}
				$this->_isValidated[true] = $key;
				$this->_isValidated[false] = true;
				return empty($this->_validationErrors);
				break;
			case is_array($key):
				foreach ($key as $eachKey) {
					unset($this->_validationErrors[$eachKey]);
					unset($this->_isValidated[$eachKey]);
					$eachVal = $this->$eachKey;
					$errors = $this->_getSchema()->field($eachKey)->getErrors($eachVal);
					if (!empty($errors)) {
						$this->_validationErrors[$eachKey] = $errors;
					}
					$this->_isValidated[$eachKey] = true;
				}
				$errors = array_intersect_key($this->_validationErrors, array_flip($key));
				$ret = empty($errors);
				// this actually shouldn't make sense as [true] and [false] shall be unset upon prop setting
				if ($ret) {
					unset($this->_isValidated[true]);
					unset($this->_isValidated[false]);
				}
				break;
			case $this->hasField($key):
			case $this->hasRelative($key):
				unset($this->_validationErrors[$key]);
				$val = $this->$key;
				$errors = $this->_getSchema()->$key->getErrors($val);
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
			case $key === false:
				if (!$this->_isValidated[$key]) {
					$this->validate($key);
				}
				return $this->_validationErrors;
			case is_array($key):
				if ($this->_isValidated[true]);
				else {
					foreach ($key as $eachKey) {
						if (!isset($this->_isValidated[$eachKey]) || !$this->_isValidated[$eachKey]) {
							$this->validate($key);
							break;
						}
					}
				}
				return array_intersect_key($this->_validationErrors, array_flip($key));
			case $this->hasField($key):
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
