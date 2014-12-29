<?php

namespace maui;

// @todo remove ID flattening when implementing REF_AUTO so the same ID representation can match ID only and full object as well
abstract class Model implements \IteratorAggregate {

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
	 * @var \ModelData
	 */
	private $_ModelData;

	/**
	 * @var \ModelBubbler
	 */
	private $_ModelBubbler;

	/**
	 * @var \ModelValidation
	 */
	private $_ModelValidation;

	////////////////////////////////////////////////////////////////////////////////
	// basic & magic
	////////////////////////////////////////////////////////////////////////////////

	public function __construct($idOrData=null, $dataIsOriginal=false) {

		static::__init();

		if (is_array($idOrData)) {
			$this->Data()->apply($idOrData, true, $dataIsOriginal ? \ModelManager::DATA_ORIGINAL : \ModelManager::DATA_CHANGED);
		}
		elseif (is_object($idOrData) && $idOrData instanceof \MongoId) {
			if ($dataIsOriginal) {
				$this->_originalData[\SchemaManager::KEY_ID] = $idOrData;
			}
			else {
				$this->Data()->setField(\SchemaManager::KEY_ID, $idOrData);
			}
		}
		elseif (is_string($idOrData)) {
			$id = new \MongoId($idOrData);
			if ($dataIsOriginal) {
				$this->_originalData[\SchemaManager::KEY_ID] = $id;
			}
			else {
				$this->Data()->setField(\SchemaManager::KEY_ID, $id);
			}
		}

	}

	public function __get($key) {
		return $this->Data()->getField($key);
	}

	public function __set($key, $val) {
		return $this->Data()->setField($key, $val);
	}

	public function __isset($key) {
		return $this->Data()->fieldIsSet($key);
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
	 * I return my schema and cache in static. Also call __init() if necessary
	 * @return \Schema
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

		if (($collectionClassname !== 'Collection') && class_exists($collectionClassname)) {
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
	//	CRUD etc - I'm tempted to extract all these to a behaviour class
	////////////////////////////////////////////////////////////////////////////////

	/**
	 * I return finder object for my class
	 * @return \ModelFinder
	 */
	public static function Finder() {

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
		$loadData = $this->Data()->flatData(\ModelManager::DATA_ALL, false, true);
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
			$this->Data()->mergeData($data);
		}
		return $this;
	}

	/**
	 * I load _originalData by original data. Won't touch actual data in _data
	 */
	public function loadOriginalData() {
		$Collection = static::_getDbCollection();
		$findData = $this->Data()->flatData(\ModelManager::DATA_ORIGINAL, false, true);
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
	public function save($deep=true, $whichData=\ModelManager::DATA_ALL, &$excludedObjectIds=array()) {

		if (!$this->Validation()->validate(false)) {
			return false;
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
				if (($EachField instanceof \SchemaFieldRelative) && ($this->Data()->fieldNotNull($eachKey, $whichData))) {
					$Relative = $this->Data()->getField($eachKey, $whichData, false);
					if ($EachField->getReference() == \SchemaManager::REF_REFERENCE) {
						// if set but empty, save() won't save it anyway
						$Relative->save(true, $whichData, $excludedObjectIds);
					}
				}

			}
		}

		if (($whichData === \ModelManager::DATA_CHANGED) && empty($this->_data)) {
			return null;
		}

		$this->_beforeSave($whichData);

		$data = $this->Data()->flatData($whichData);
		$DbCollection = static::_getDbCollection();

		if ($this->_id) {
			unset($data['_id']);
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
				if (isset($this->_data[$eachKey]) && ($this->_data[$eachKey]=== $this->_originalData[$eachKey])) {
					unset($this->_data[$eachKey]);
				}
			}
		}

		return $result;
	}

	/**
	 * I will be called before save's validation (so if it sets a value, it must be valid)
	 * only I call myself recursively for relative objects so I am safe being protected
	 *
	 * @param $whichData as in ModelManager constants
	 */
	public function _beforeSave($whichData, &$excludedObjectIds=array()) {

		$objectHash = spl_object_hash($this);
		if (in_array($objectHash, $excludedObjectIds)) {
			return;
		}
		$excludedObjectIds[] = $objectHash;

		$Schema = static::_getSchema();
		foreach ($Schema as $eachKey=>$EachField) {
			// recursive if field is inline relative
			if ($EachField instanceof \maui\SchemaFieldRelative &&
				($EachField->getReference() === \SchemaManager::REF_INLINE) &&
				!$this->Data()->fieldIsEmpty($eachKey, $whichData)) {
				$eachVal = $this->Data()->getField($eachKey, $whichData);
				// I loop the Collection manually so I can call _beforeSave directly
				if ($EachField->isMulti()) {
					foreach ($eachVal as $EachValItem) {
						$EachValItem->_beforeSave($whichData);
					}
				}
				else {
					$eachVal->_beforeSave($whichData);
				}
			}
			$EachField->beforeSave($eachKey, $this);
		}
	}

	////////////////////////////////////////////////////////////////////////////////
	// data and field related
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
			$modelData = $modelData->Data()->getData(true, \ModelManager::DATA_ALL, true);
		}
		if ($data instanceof \Model) {
			$data = $data->Data()->getData(true, \ModelManager::DATA_ALL, true);
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
	 * I return data helper
	 * @return \ModelData
	 */
	public function Data() {
		if (is_null($this->_ModelData)) {
			$ModelDataClassName = \Finder::getFriendClassOf($this, 'Data', 'Model');
			$this->_ModelData = new $ModelDataClassName($this);
		}
		return $this->_ModelData;
	}

	/**
	 * I return bubbler helper
	 * @return \ModelBubbler
	 * @see \maui\ModelBubbler
	 */
	public function Bubbler() {
		if (is_null($this->_ModelBubbler)) {
			$ModelBubblerClassName = \Finder::getFriendClassOf($this, 'Bubbler', false);
			$this->_ModelBubbler = new $ModelBubblerClassName($this);
		}
		return $this->_ModelBubbler;
	}

	/**
	 * I return validation helper
	 * @return \ModelValidation
	 */
	public function Validation() {
		if (is_null($this->_ModelValidation)) {
			$validationClassname = \Finder::getFriendClassOf($this, 'Validation', 'Model');
			$this->_ModelValidation = new $validationClassname($this);
		}
		return $this->_ModelValidation;
	}

}
