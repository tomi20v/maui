<?php

namespace maui;

abstract class Model implements \IteratorAggregate {

	use \maui\TraitHasLabel;

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

		static::__init();

		if (is_array($idOrData)) {
			$this->apply($idOrData, true, $dataIsOriginal ? \ModelManager::DATA_ORIGINAL : \ModelManager::DATA_CHANGED);
		}
		elseif (is_object($idOrData) && $idOrData instanceof \MongoId) {
			if ($dataIsOriginal) {
				$this->_originalData[\SchemaManager::KEY_ID] = $idOrData;
			}
			else {
				$this->field(\SchemaManager::KEY_ID, $idOrData);
			}
		}
		elseif (is_string($idOrData)) {
			$id = new \MongoId($idOrData);
			if ($dataIsOriginal) {
				$this->_originalData[\SchemaManager::KEY_ID] = $id;
			}
			else {
				$this->field(\SchemaManager::KEY_ID, $id);
			}
		}

	}

	public function __get($key) {
		return $this->field($key);
	}

	public function __set($key, $val) {
		return $this->field($key, $val);
	}

	/**
	 * I must be called before anything (__construct() does it)
	 */
	public static function __init() {

		if (static::$_schema === true) {
			return;
		}

		$classname = get_called_class();

		if (empty(static::$_schema)) {
			throw new \Exception('schema must not be empty, saw empty in ' . $classname);
		}

		\SchemaManager::registerSchema(
			$classname,
			\SchemaManager::ensureHasId(static::$_schema)
		);

		static::$_schema = true;
		\ModelManager::registerInited($classname);

	}

	/**
	 * I iterate my schema
	 * @return \ArrayIterator|\Traversable
	 */
	public function getIterator() {
		return new \ArrayIterator(static::_getSchema());
	}

	/**
	 * @return \Schema I return my schema and cache in static. Also call __init() if necessary
	 */
	protected static function _getSchema() {
		static $Schema;
		if (is_null($Schema)) {
			static::__init();
			$Schema = \SchemaManager::getSchema(get_called_class());
		}
		return $Schema;
	}

	////////////////////////////////////////////////////////////////////////////////
	//	collection
	////////////////////////////////////////////////////////////////////////////////

	/**
	 * I return the DB collection objection for load save etc
	 * @return \MongoCollection
	 */
	protected static function _getDbCollection() {
		static $_DbCollection;
		if (is_null($_DbCollection)) {
			$collectionClassname = static::getCollectionClassname();
			$dbCollectionName = $collectionClassname::getDbCollectionName(get_called_class());
			$_DbCollection = \Maui::instance()->dbDb()->$dbCollectionName;
		}
		return $_DbCollection;
	}

	/**
	 * I return empty collection for this class
	 * @param null $data
	 * @return mixed
	 * @extendMe eg. in case reusing a collection class for multiple models
	 */
	public static function getCollection($data=null) {
		$collectionClassname = static::getCollectionClassname();
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
	public static function getCollectionClassname() {
		$collectionClassname = ucfirst(get_called_class()) . 'Collection';
		if (!class_exists($collectionClassname)) {
			$parentClassname = get_parent_class(get_called_class());
			$collectionClassname = $parentClassname === false
				? 'Collection'
				: $parentClassname::getCollectionClassname();
		}
		return $collectionClassname;
	}

	////////////////////////////////////////////////////////////////////////////////
	//	CRUD etc
	////////////////////////////////////////////////////////////////////////////////

	/**
	 * @param mixed[] $loadData prepared data example to load by
	 */
	public static function loadAsSaved($loadData) {
		return static::loadAs($loadData);
	}

	/**
	 * @param mixed[] $loadData prepared data example to load by
	 * @param string $classname will return an object of this class
	 */
	public static function loadAs($loadData, $classname=null) {
		$Collection = static::_getDbCollection();
		$data = $Collection->findOne($loadData);
		$data = is_null($data) ? array() : $data;
		if (is_null($classname)) {
			$classname = isset($data['_type']) ? $data['_type'] : get_called_class();
		}
		if (!class_exists($classname, false)) {
			if (!class_exists($classname)) {
				throw new \Exception('class source cannot be loaded for ' . $classname);
			}
			$classname::__init();
		}
		$data = \SchemaManager::filterBySchema($data, $classname);
		$Model = new $classname($data, true);
		return $Model;
	}

	/**
	 * I load by attributes
	 * @param bool $loadEmpty if true, I will load even if data to select is empty
	 * @return $this
	 */
	public function OBSload($loadEmpty=false) {
		$Collection = static::_getDbCollection();
		$loadData = $this->getData(\ModelManager::DATA_ALL);
		if (!$loadEmpty && empty($loadData)) {
			return false;
		}
		$data = $Collection->findOne($loadData);
		// I might not want to overwrite data if not found... to be checked later
		$data = is_null($data) ? array() : $data;
		$this->_originalData = $data;
		$this->_data = array();
		return $this;
	}

	/**
	 * I load _originalData by original data. Won't touch actual data in _data
	 */
	public function OBSloadOriginalData() {
		$Collection = static::_getDbCollection();
		$findData = $this->getData(\ModelManager::DATA_ORIGINAL);
		if (empty($findData)) {
			return false;
		}
		$data = $Collection->findOne($findData);
		// I might not want to overwrite data if not found... to be checked later
		$data = is_null($data) ? array() : $data;
		$this->_originalData = $data;
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
		throw new \Exception('TBI');
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
	public function OBSsave($fieldsOrDeepsave=true, &$excludedObjectIds=array()) {

		$DbCollection = static::_getDbCollection();
		// do deep validation and save
		switch (true) {
		case ($fieldsOrDeepsave === true):
			// first save referenced relatives
			$objectHash = spl_object_hash($this);
			if (in_array($objectHash, $excludedObjectIds)) {
				continue;
			}
			$excludedObjectIds[] = $objectHash;

			$Schema = static::_getSchema();
			foreach ($Schema as $eachKey=>$EachField) {
				if (($EachField instanceof \SchemaRelative) &&
					($EachField->getReference() == \SchemaManager::REF_REFERENCE)) {
					$Relative = $this->val($eachKey);
					if (!is_null($Relative)) {
						$Relative = $this->_relative($eachKey);
						$Relative->save(true, $excludedObjectIds);
					}
				}

			}
		// FALLTHROUGH
		case ($fieldsOrDeepsave === false):
			if (empty($this->_data)) {
				return $this;
			}
			$this->_beforeSave();
			if (!$this->validate(false)) {
				return null;
			}

			$whichData = \ModelManager::DATA_ALL;
			if (is_array($fieldsOrDeepsave)) {
				$whichData = $fieldsOrDeepsave;
			}
			elseif ($fieldsOrDeepsave === false) {
				$whichData = \ModelManager::DATA_CHANGED;
			}
			$data = $this->getData($whichData);
#			return $data;
			if ($this->_id) {
				$result = $DbCollection->update(
					array(\SchemaManager::KEY_ID => $this->_id),
					array('$set' => $data)
				);
			}
			else {
				if (empty($data)) {
					return null;
				}
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
		case is_array($fieldsOrDeepsave):
			throw new \Exception('TBI');
		}
		return null;
	}

	/**
	 * I will be called before save's validation (so if it sets a value, it must be valid)
	 * I call myself recursively for relative objects so I am safe being protected
	 */
	protected function OBS_beforeSave() {
		$Schema = static::_getSchema();
		foreach ($Schema as $eachKey=>$EachField) {
			if ($EachField instanceof \SchemaAttr) {
				$EachField->beforeSave($eachKey, $this);
			}
			elseif (!empty($eachVal)) {
				$eachVal = is_null($this->val($eachKey)) ? null : $this->_relative($eachKey);
				if (is_null($eachVal));
				elseif ($eachVal instanceof \Collection) {
					foreach ($eachVal as $EachObject) {
						$EachObject->_beforeSave();
					}
				}
				else {
					if(!is_object($eachVal)) {
						echop($this); echop($eachKey); echop($eachVal); debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS); die;
					}
					$eachVal->_beforeSave();
				}
			}
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
			$modelData = $modelData->getData(true, \ModelManager::DATA_ALL, true);
		}
		if ($data instanceof \Model) {
			$data = $data->getData(true, \ModelManager::DATA_ALL, true);
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
	 * I return array representation of current data state
	 * @param array|bool $keys field keys to return, true to return all. still, I will return only fields which are set
	 * @param int $whichData as in ModelManager
	 * @param bool $asIs if true, data is returned as is. If false, relatives will be constructed from existing data
	 * @return array|null
	 */
	public function getData($keys=true, $whichData=\ModelManager::DATA_ALL, $asIs=true) {

		if ($asIs) {
			switch ($whichData) {
			case \ModelManager::DATA_CHANGED:
				$data = $this->_data;
				break;
			case \ModelManager::DATA_ORIGINAL:
				$data = $this->_originalData;
				break;
			case \ModelManager::DATA_ALL:
				$data = $this->_data + $this->_originalData;
				break;
			}
			if (is_array($keys)) {
				$data = array_intersect_key($data, array_flip($keys));
			}
			return $data;
		}

		$data = array();
		$Schema = static::_getSchema();

		foreach ($Schema as $eachKey=>$EachField) {
			$data[$eachKey] = $this->getField($eachKey, $whichData, false);
		}

		return $data;

	}

	/**
	 * I return multidimensional array of just scalar values (models and collections transformed to their array representation)
	 * @param int $whichData as in ModelManager
	 * @return array
	 */
	public function flatData($whichData=\ModelManager::DATA_ALL) {

		$data = $this->getData(true, $whichData, true);

		return static::_flatData($data, $whichData);

	}

	/**
	 * I flatten an array so models and collections are transformed to their array representation
	 * @param $data
	 * @param $whichData
	 * @return array
	 */
	protected static function _flatData($data, $whichData) {

		if (is_array($data)) {
			foreach ($data as $eachKey=>$eachVal) {
				if ($eachVal instanceof \Collection) {
					$eachData = $eachVal->flatData($whichData);
				}
				elseif ($eachVal instanceof \Model) {
					$eachData = $eachVal->getData(true, $whichData, true);
				}
				elseif (is_array($eachVal)) {
					$eachData = $eachVal;
				}
				else {
					continue;
				}
				foreach ($eachData as $eachDataKey=>$eachDataVal) {
					$eachData[$eachDataKey] = static::_flatData($eachDataVal, $whichData);
				}
				$data[$eachKey] = $eachData;
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
	public static function hasField($key) {
		return static::_getSchema()->hasField($key);
	}

	/**
	 * I set or get a field. I always return an object for relatives. Magics wrap me.
	 * internally, I just wrap attr() and relative() methods
	 * @param string $key field name to set or get
	 * @param null|mixed $val if present, field will be set, otherwise just returned
	 * @return $this|Model|null I return $this on set, or mixed for get. Note:
	 * 	field($key) returns actual data, with fallback to return original data
	 * @throws \Exception
	 */
	public function field($key, $val=null) {
		if (func_num_args() == 1) {
			if ($this->hasAttr($key)) {
				return $this->_getAttr($key, \ModelManager::DATA_ALL, false);
			}
			elseif ($this->hasRelative($key)) {
				return $this->_getRelative($key, \ModelManager::DATA_ALL, false);
			}
		}
		else {
			if ($this->hasAttr($key)) {
				return $this->_setAttr($key, $val, \ModelManager::DATA_ALL);
			}
			elseif ($this->hasRelative($key)) {
				return $this->_setRelative($key, $val, \ModelManager::DATA_ALL);
			}
		}
		throw new \Exception('field ' . $key . ' does not exists in class ' . get_class($this));
	}

	public function getField($key, $whichData=\ModelManager::DATA_CHANGED, $asIs=false) {
		if ($this->hasAttr($key)) {
			return $this->_getAttr($key, $whichData, $asIs);
		}
		elseif ($this->hasRelative($key)) {
			return $this->_getRelative($key, $whichData, $asIs);
		}
	}

	public function setField($key, $val, $whichData=\ModelManager::DATA_CHANGED) {
		if ($this->hasAttr($key)) {
			return $this->_setAttr($key, $val, $whichData);
		}
		elseif ($this->hasRelative($key)) {
			return $this->_setRelative($key, $val, $whichData);
		}
	}

	/**
	 * I return if a fields value is set. I do not validate $key param
	 * @param string $key
	 * @param int $whichData
	 * @return bool
	 * @throws \Exception
	 */
	public function fieldIsSet($key, $whichData = \ModelManager::DATA_ALL) {
		switch($whichData) {
		case \ModelManager::DATA_CHANGED:
			return array_key_exists($key, $this->_data);
		case \ModelManager::DATA_ORIGINAL:
			return array_key_exists($key, $this->_originalData);
		case \ModelManager::DATA_ALL:
			return array_key_exists($key, $this->_data) || array_key_exists($key, $this->_originalData);
		}
		throw new \Exception('invalid value (' . echon($whichData) . ' for $whichData in fieldIsSet()');
	}

	/**
	 * I return if I have an attribute called $key
	 * @param $key
	 * @return bool
	 */
	public function hasAttr($key) {
		return static::_getSchema()->hasAttr($key);
	}

	/**
	 * I return an attribute
	 * @param string $key
	 * @param int $whichData
	 * @param bool $asIs not used here
	 * @return null
	 */
	protected function _getAttr($key, $whichData, $asIs) {
		$val = isset($this->_data[$key]) ? $this->_data[$key] : null;
		$originalVal = isset($this->_originalData[$key]) ? $this->_originalData[$key] : null;
		switch ($whichData) {
		case \ModelManager::DATA_CHANGED:
			return $val;
		case \ModelManager::DATA_ORIGINAL:
			return $originalVal;
		case \ModelManager::DATA_ALL:
			return isset($val) ? $val : $originalVal;
		}
	}

	/**
	 * @param string $key
	 * @param mixed $val value to set
	 * @param int $whichData
	 */
	protected function _setAttr($key, $val, $whichData) {
		switch ($whichData) {
		case \ModelManager::DATA_CHANGED:
		case \ModelManager::DATA_ALL:
			$this->_data[$key] = $val;
			break;
		case \ModelManager::DATA_ORIGINAL:
			$this->_originalData[$key] = $val;
			break;
		}
		return $this;
	}

	/**
	 * I return if I have relative(s) called $key
	 * @param $key
	 * @return bool
	 */
	public function hasRelative($key) {
		return static::_getSchema()->hasRelative($key);
	}

	/**
	 * I return or set relative on field $field
	 *
	 * @param $key
	 * @param $this|mixed $value
	 */
	protected function _relative($key, $val=null) {
		$ret = null;
		if (count(func_get_args()) == 1) {
			if (!array_key_exists($key, $this->_data) && array_key_exists($key, $this->_originalData)) {
				// @todo I should implement deep cloning
				$this->_data[$key] = clone $this->_originalData[$key];
			}
			if (array_key_exists($key, $this->_data)) {
				$ret = $this->_data[$key];
				if (is_object($ret) && !($ret instanceof \MongoId));
				else {
					$Rel = static::_getSchema()->field($key);
					$ret = $Rel->getReferredObject($this->_data[$key]);
					$this->_data[$key] = $ret;
				}
			}
		}
		else {
			$Rel = static::_getSchema()->field($key);
			$classname = $Rel->getObjectClassname();
			if ($val instanceof \Model) {
				if (!$val instanceof $classname) {
					throw new \Exception('cannot set ' . echon($val) . ' for field ' . echon($key) . ' as it is not subclass of ' . echon($classname));
				}
			}
			elseif ($val instanceof \Collection) {
				if (!$Rel->isMulti()) {
					throw new \Exception('cannot set collection for field ' . echon($key) . ' as is not multi');
				}
				$collectionClassname = $classname::getCollectionName();
				if (!$val instanceof $collectionClassname) {
					throw new \Exception('cannot set collection ' . echon($val) . ' for field ' . echon($key) . ' as it is not subclass of ' . echon($classname));
				}
			}
			$this->_data[$key] = $val;
			$ret = $this;
		}
		return $ret;
	}

	/**
	 * @param string $key
	 * @param int $whichData as in ModelManager::DATA_* constants
	 * @param bool $asIs get relative value as is (eg. return empty or just mongoId object) or create (and set) proper object
	 * @return null
	 */
	protected function _getRelative($key, $whichData, $asIs) {

		$val = isset($this->_data[$key]) ? $this->_data[$key] : null;
		$originalVal = isset($this->_originalData[$key]) ? $this->_originalData[$key] : null;

		// $asIs = false - return some meaningful object, create (and set) if necessary
		// this shall be more savvy
		switch ($whichData) {
		case \ModelManager::DATA_CHANGED:
			$ret = $val;
			break;
		case \ModelManager::DATA_ORIGINAL:
			$ret = $val;
			break;
		case \ModelManager::DATA_ALL:
			$ret = isset($val) ? $val : $originalVal;
			break;
		}

		if ($asIs);
		elseif (is_object($ret) && !($ret instanceof \MongoId));
		else {

			$Rel = static::_getSchema()->field($key);
			$ret = $Rel->getReferredObject($ret);

			switch ($whichData) {
			case \ModelManager::DATA_CHANGED:
			case \ModelManager::DATA_ALL:
				$this->_data[$key] = $ret;
				break;
			case \ModelManager::DATA_ORIGINAL:
				$this->_originalData[$key] = $ret;
				break;
			}

		}

		return $ret;

	}

	protected function _setRelative($key, $val, $whichData) {

		$Rel = static::_getSchema()->field($key);

		$Rel->checkVal($val);

		switch ($whichData) {
		case \ModelManager::DATA_CHANGED:
		case \ModelManager::DATA_ALL:
			$this->_data[$key] = $val;
			break;
		case \ModelManager::DATA_ORIGINAL:
			$this->_originalData[$key] = $val;
			break;
		}

		return $this;

	}

	/**
	 * I set or merge data
	 * @param mixed[] $data data to use
	 * @param bool $overwrite if true, I set originalData. Otherwise, I add $data to
	 * 		current $_data. NOTE collections are overwritten this way (maybe fix this?)
	 * @return $this
	 * @throws \Exception
	 */
	public function OBSapply($data, $dataIsOriginal=false) {
		if (!is_array($data)) {
			throw new \Exception(echon($data));
		}
		elseif($dataIsOriginal) {
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

	/**
	 * I set or merge data
	 * @param mixed[] $data
	 * @param bool $overWrite if true, $data is set as data erasing old, otherwise they're merged
	 * @param int $whichData as in ModelManager constants
	 * @return $this
	 * @throws \Exception
	 */
	public function apply($data, $overWrite=false, $whichData=\ModelManager::DATA_CHANGED) {

		if (!is_array($data)) {
			throw new \Exception('can apply only an array of data, got: ' . echon($data));
		}

		switch ($whichData) {
		case \ModelManager::DATA_ORIGINAL:
			$dataStore = &$this->_originalData;
			break;
		case \ModelManager::DATA_CHANGED:
		case \ModelManager::DATA_ALL:
			$dataStore = &$this->_data;
			break;
		}

		if ($overWrite) {
			$dataStore = $data;
		}
		else {
			foreach ($data as $eachKey=>$eachVal) {
				$this->setField($eachKey, $eachVal, $whichData);
			}
		}

		return $this;

	}

	////////////////////////////////////////////////////////////////////////////////
	// validation
	////////////////////////////////////////////////////////////////////////////////

	/**
	 * I am a fast teller if object has any field value set (so no need to call for a getData())
	 * @param string|string[] array of fieldnames to exclude from comparison
	 * @return bool
	 */
	public function isEmpty($excludeKeys) {
		$excludeKeys = (array) $excludeKeys;
		$keys = array_diff(array_keys($this->_data), $excludeKeys);
		return empty($keys);
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
				$Schema = static::_getSchema();
				foreach ($Schema as $eachKey=>$EachField) {
					if (!$key && !array_key_exists($eachKey, $this->_data)) {
						continue;
					}
					$eachVal = $this->getField($eachKey, \ModelManager::DATA_ALL, true);
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
					$errors = static::_getSchema()->field($eachKey)->getErrors($eachVal);
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
				return count(array_intersect_key($this->_validationErrors, array_flip($key))) ? false : true;
				break;
			case $this->hasField($key):
				return $this->validate(array($key));
			default:
				throw new \Exception('cannot validate non existing field def: ' . echon($key));
		}
	}

	public function getErrors($key=true) {
		switch(true) {
			case $key === true:
			case $key === false:
				if (!isset($this->_isValidated[$key]) || !$this->_isValidated[$key]) {
					$this->validate($key);
				}
				return $this->_validationErrors;
			case is_array($key):
				if (isset($this->_isValidated[true]) && $this->_isValidated[true]);
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
				return $this->getErrors(array($key));
			default:
				throw new \Exception('cannot get error for non existing field def: ' . echon($key));;
		}
	}

}
