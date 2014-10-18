<?php

namespace maui;

// @todo remove ID flattening when implementing REF_AUTO so the same ID representation can match ID only and full object as well
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

	public function __isset($key) {
		return $this->fieldIsSet($key);
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
			$dbCollectionName = static::getDbCollectionName();
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

	/**
	 * @return string
	 */
	public static function getDbCollectionName() {
		return 'Collection';
	}

	////////////////////////////////////////////////////////////////////////////////
	//	CRUD etc
	////////////////////////////////////////////////////////////////////////////////

	/**
	 * I return finder object for my class
	 * @return \ModelFinder
	 */
	public static function finder() {

		return new \ModelFinder(get_called_class());

	}

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
		/**
		 * @var \Model $Model
		 */
		$Model = new $classname($data, true);
		return $Model;
	}

	/**
	 * I return if model is loaded. I consider loaded any model whose originaldata contains more than the _id and _type
	 * @return bool
	 */
	public function isLoaded() {
		$keys = array('_id', '_type');
		if (!empty($this->_originalData) && count(array_diff_key($this->_originalData, array_flip($keys)))) {
			return true;
		}
		return false;
	}

	/**
	 * I load by attributes
	 * @param bool $loadEmpty if true, I will load even if data to select is empty
	 * @return $this
	 */
	public function load($loadEmpty=false) {
		$loadData = $this->flatData(\ModelManager::DATA_ALL, false, true);
		if (!$loadEmpty && empty($loadData)) {
			return false;
		}
		return $this->loadBy($loadData);
	}

	/**
	 * I load by data directly (data can be a mongo query document in array)
	 * @param $loadData
	 * @return $this
	 */
	public function loadBy($loadData) {
		$Collection = static::_getDbCollection();
		$data = $Collection->findOne($loadData);
		// I might not want to overwrite data if not found... to be checked later
		if (is_null($data)) {
			// do nothing for now, see comment
		}
		else {
			$this->mergeData($data);
		}
		return $this;
	}

	/**
	 * I load _originalData by original data. Won't touch actual data in _data
	 */
	public function loadOriginalData() {
		$Collection = static::_getDbCollection();
		$findData = $this->flatData(\ModelManager::DATA_ORIGINAL, false, true);
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

	/**
	 * I save myself
	 * @param bool $deep if true, relatives save will be triggered as well
	 * @param null $whichData as in ModelManager. Default depends on $deep, see code
	 * @return bool true on success
	 */
	public function save($deep=true, $whichData=null, &$excludedObjectIds=array()) {
		if (is_null($whichData)) {
			$whichData = $deep ? \ModelManager::DATA_ALL : \ModelManager::DATA_CHANGED;
		}
		return $this->_save($deep, $whichData, $excludedObjectIds);
	}

	/**
	 * inner save method
	 * @param bool $deep
	 * @param $whichData
	 * @param $excludedObjectIds
	 * @return bool
	 */
	protected function _save($deep, $whichData, &$excludedObjectIds) {

		if ($deep) {
			$objectHash = spl_object_hash($this);
			if (in_array($objectHash, $excludedObjectIds)) {
				return;
			}
			$excludedObjectIds[] = $objectHash;
			$Schema = static::_getSchema();
			foreach ($Schema as $eachKey=>$EachField) {
				if (($EachField instanceof \SchemaFieldRelative) &&
					($EachField->getReference() == \SchemaManager::REF_REFERENCE)) {
					// save only if set. If set but empty, save() won't save it anyway
					//if ($this->fieldIsSet($eachKey, $whichData)) {
					if ($this->fieldNotNull($eachKey, $whichData)) {
						$Relative = $this->_getRelative($eachKey, $whichData, false);
						$Relative->save($whichData, true, $excludedObjectIds);
					}
				}

			}
		}

		if (($whichData === \ModelManager::DATA_CHANGED) && empty($this->_data)) {
			return null;
		}

		$this->_beforeSave($whichData);

		if (!$this->validate(false)) {
			return false;
		}

		$data = $this->flatData($whichData, true, true);
		$DbCollection = static::_getDbCollection();

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
			$result = $DbCollection->save($data);
		}

		if (isset($result['ok']) && $result['ok']) {
			foreach ($data as $eachKey=>$eachVal) {
				$this->_originalData[$eachKey] = $eachVal;
				unset($this->_data[$eachKey]);
			}
		}

		return $result;
	}

	/**
	 * I save just certain fields from a model for faster updates
	 * @param string[] $fields to save
	 */
	public function saveFields($fields) {

	}

	/**
	 * I will be called before save's validation (so if it sets a value, it must be valid)
	 * only I call myself recursively for relative objects so I am safe being protected
	 *
	 * @param $whichData as in ModelManager constants
	 */
	protected function _beforeSave($whichData) {
		$Schema = static::_getSchema();
		foreach ($Schema as $eachKey=>$EachField) {
			$EachField->beforeSave($eachKey, $this);
		}
	}

	////////////////////////////////////////////////////////////////////////////////
	// data related general
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
			default:
				throw new \Exception('invalid value for $whichData: ' . echon($whichData));
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
	 * I return data representation in a multi dimensional array, suitable for save. This same data can be set for the
	 * 		proper model and it shall be the same as before save.
	 * @param int $whichData
	 * @return array multidimensional array of just scalar values
	 * @throws \Exception
	 */
	public function flatData($whichData=\ModelManager::DATA_ALL) {

		$data = array();

		$Schema = static::_getSchema();

		foreach ($Schema as $eachKey=>$EachField) {
			if (!$this->fieldIsSet($eachKey, $whichData)) {
				continue;
			}
			$eachVal = $this->getField($eachKey, $whichData, true);
			if (is_null($eachVal)) {

			}
			elseif (($eachVal instanceof \Model) && ($EachField instanceof \SchemaFieldRelative)) {
				$eachVal = $EachField->getReference() === \SchemaManager::REF_INLINE
					? $eachVal->flatData($whichData)
					: (is_null($eachVal->_id) ? null : '' . $eachVal->_id);
			}
			elseif ($EachField->isMulti() && is_array($eachVal)) {
				foreach ($eachVal as $eachValKey=>$eachValVal) {
					if (($eachValVal instanceof \Model) && ($EachField instanceof \SchemaFieldRelative)) {
						$eachVal[$eachValKey] = $EachField->getReference() === \SchemaManager::REF_INLINE
							? $eachValVal->flatData($whichData)
							: '' . $eachValVal->_id;
					}
				}
			}
			elseif ($eachVal instanceof \Collection) {
				throw new \Exception('TBI');
			}
			$data[$eachKey] = $eachVal;
		}

		return $data;

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

		if ($overWrite) {

			switch ($whichData) {
			case \ModelManager::DATA_ORIGINAL:
				$dataStore = &$this->_originalData;
				break;
			case \ModelManager::DATA_CHANGED:
			case \ModelManager::DATA_ALL:
				$dataStore = &$this->_data;
				break;
			}

			$dataStore = $data;

		}
		else {
			foreach ($data as $eachKey=>$eachVal) {
				$this->setField($eachKey, $eachVal, $whichData);
			}
		}

		return $this;

	}

	public function mergeData($data = null) {

		// @todo I should write this unrolled and low level and apply optimizations
		$this->apply($this->_data, false, ModelManager::DATA_ORIGINAL);
		$this->_data = array();

		if (!is_null($data)) {
			$this->apply($data, false, ModelManager::DATA_ORIGINAL);
		}

	}

	////////////////////////////////////////////////////////////////////////////////
	// data related, fields
	////////////////////////////////////////////////////////////////////////////////

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

	/**
	 * I return a field. I wrap _getAttr() and _getRelative
	 * @param $key
	 * @param int $whichData
	 * @param bool $asIs
	 * @return null
	 */
	public function getField($key, $whichData=\ModelManager::DATA_CHANGED, $asIs=false) {
		if ($this->hasAttr($key)) {
			return $this->_getAttr($key, $whichData, $asIs);
		}
		elseif ($this->hasRelative($key)) {
			return $this->_getRelative($key, $whichData, $asIs);
		}
	}

	/**
	 * I set a fields value. I wrap _setAttr() and _setRelative()
	 * @param $key
	 * @param $val
	 * @param int $whichData
	 * @return $this
	 */
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

	public function fieldNotNull($key, $whichData = \ModelManager::DATA_ALL) {
		switch($whichData) {
		case \ModelManager::DATA_CHANGED:
			return isset($this->_data[$key]);
		case \ModelManager::DATA_ORIGINAL:
			return isset($this->_originalData[$key]);
		case \ModelManager::DATA_ALL:
			return isset($this->_data[$key]) || isset($this->_originalData[$key]);
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
	 * @param bool $asIs if false, and field is multi, cast it to array
	 * @return null
	 */
	protected function _getAttr($key, $whichData, $asIs) {
		$val = isset($this->_data[$key]) ? $this->_data[$key] : null;
		$originalVal = isset($this->_originalData[$key]) ? $this->_originalData[$key] : null;

		// if not getting data as is, and field is multi, cast it to array
		if (!$asIs && static::_getSchema()->getAttr($key)->isMulti()) {
			$val = (array) $val;
			$originalVal = (array) $originalVal;
		}

		switch ($whichData) {
		case \ModelManager::DATA_CHANGED:
			$ret = $val;
			break;
		case \ModelManager::DATA_ORIGINAL:
			$ret = $originalVal;
			break;
		case \ModelManager::DATA_ALL:
			$ret = isset($val) ? $val : $originalVal;
			break;
		}

		if (!$asIs && is_null($ret)) {
			$Attr = static::_getSchema()->getAttr($key);
			$ret = $Attr->getDefault($key, $this);
		}

		return $ret;

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
			$ret = $originalVal;
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

		/**
		 * @var \SchemaFieldRelative $Rel
		 */
		$Rel = static::_getSchema()->field($key);

		$Rel->checkVal($val);

		// do not overwrite if it's a relative, and already inflated, and incoming data is the same but by reference
		if ($Rel->getReference() === \SchemaManager::REF_REFERENCE) {
			$currentValue = $this->_getRelative($key, $whichData, true);
			// @todo implement check for collections
			if (($currentValue instanceof \Model) &&
				is_string($val) &&
				((string)$currentValue->_id === $val)) {

				return;

			}
		}

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
	 * get bubbler helper
	 * @return \ModelBubbler
	 * @see \maui\ModelBubbler
	 */
	public function getBubbler() {

		return new \ModelBubbler($this);

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

	/**
	 * I return all or some errors
	 * @param bool $key
	 * @return array|\array[]
	 * @throws \Exception
	 */
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
