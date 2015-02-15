<?php

namespace maui;

/**
 * Class Collection
 *
 * @package Maui
 * anomalies (bugs!?)
 * 	- created relative objects will have to be re-created after a save(). This does not make safe keeping a reference
 * 		of the relative object (or has to be refreshed after save). Currently marked as low value improvement
 *  - possibly solved by above: getting a relative, if it is constructed, triggers a save no matter if the any of the
 * 		child or parent object has changed
 */
class Collection implements \Arrayaccess, \Iterator, \Countable {

	/**
	 * @var null
	 * @extendMe
	 */
	protected $_modelClassname;

	/**
	 * @var mixed[] I hold an array of items in collection, either their data only or
	 * 		their instance after they're constructed
	 */
	protected $_data = array();

	/**
	 * @var int will be filled from $Cursor->count(true)
	 */
	protected $_matchedCount = null;

	/**
	 * @var int will be filled by $Cursor->count()
	 */
	protected $_pagesCount = null;

	/**
	 * @var array a simple and dumb array of filters, applicable for mongoDB
	 */
	protected $_filters = array();

	public function __construct($data=null, $modelClassname=null) {
		if (!is_null($modelClassname)) {
			$this->_modelClassname = $modelClassname;
		}
		if (!is_null($data)) {
			$this->apply($data, true);
		}
	}

	/**
	 * @param $key
	 * @return null
	 */
	public function __get($key) {
		switch ($key) {
			// provide ->_id property for compatibility with model
			case \SchemaManager::KEY_ID:
				return null;
		}
	}

	/**
	 * I return name of collection in DB
	 * @return string
	 * @extendMe - reuse another DB collection by returning its name from here
	 */
	public static function getDbCollectionName($modelClassname=null) {
		if (!empty($modelClassname)) {
			$collectionName = $modelClassname::getDbCollectionName();
		}
		else {
			$collectionName = get_called_class();
		}
		return $collectionName;
	}

	/**
	 * I return the actual DB collection to use
	 * @return \MongoCollection
	 */
	protected function _getDbCollection() {
		$collectionName = static::getDbCollectionName($this->_modelClassname);
		return \Maui::instance()->dbDb()->$collectionName;
	}

	/**
	 * I return proper model classname. To override, define $_modelClassname in class definition or override this method
	 * @return null|string
	 * @extendMe
	 */
	protected function _getModelClassname() {
		if (empty($this->_modelClassname)) {
			if (preg_match('/^(.+)Collection$/', get_class($this), $matches)) {
				$this->_modelClassname = $matches[1];
				if ($pos = strrpos($this->_modelClassname, '\\')) {
					$this->_modelClassname = substr($this->_modelClassname, $pos+1);
				}
			}
		}
		return $this->_modelClassname;
	}

	/**
	 * I apply data from array. Can be array of data from DB or array of models
	 * @param array[]|\Model[] $data
	 * @param bool $replace - send true to replace current data (also faster). Otherwise $data is merged. Send null
	 * 		to disable duplicate checking
	 * @return $this
	 * @throws \Exception
	 */
	public function apply($data, $replace=true) {
		if (!is_array($data)) {
			throw new \Exception(echon($data));
		}
		if ($replace) {
			$this->_data = $data;
		}
		else {
			foreach ($data as $eachKey=>$eachData) {
				if (is_numeric($eachKey)) {
					$this->add($eachData, null, true);
				}
				else {
					$this->add($eachData, $eachKey, false);
				}

			}
		}
		return $this;
	}

	public function clear() {
		throw new \Exception('TBI');
	}

	/**
	 * add one or more models, by data, by model, or by collection
	 *
	 * @param \Model|array $data
	 * @param string $key
	 * @param bool if false, I won't check if item is already in (probable to result in duplicates with numeric keys)
	 * @return $this
	 * @throws \Exception
	 */
	public function add($data, $key, $checkDuplicates=true) {
		// @todo support adding collections as well
		if (is_array($data) || ($data instanceof \maui\Model));
		else {
			throw new \Exception(echon($data) . ' / ' . echon($checkDuplicates));
		}
		if (is_numeric($key)) {
			$key = null;
		}
		if ($checkDuplicates && $this->contains($data));
		else {
			if (is_null($key)) {
				$this->_data[] = $data;
			}
			else {
				$this->_data[$key] = $data;
			}
		}
		return $this;
	}

	public function append($data, $checkDuplicates=true) {
		if (empty($data)) {
			return $this;
		}
		if ($data instanceof \Model) {
			$data = [$data];
		}
		if (!is_array($data) && !($data instanceof \Collection)) {
			throw new \Exception('cannot add: ' . echon($data));
		}
		foreach ($data as $eachKey=>$eachVal) {
			$this->add($eachVal, $eachKey, $checkDuplicates);
		}
	}

	public function remove($ModelOrModels) {
		throw new \Exception('TBI');
	}

	/**
	 * I check if submitted model or data matches a model or data in me. Match means
	 * 		that my data contains all keys and same values as $ModelOrData.
	 * @param $ModelOrData
	 * @return bool
	 */
	public function contains($ModelOrData) {
		$data = $ModelOrData instanceof \Model
			? $ModelOrData->Data()->getData(true, \ModelManager::DATA_ALL, true)
			: $ModelOrData;
		foreach ($this->_data as $eachModel) {
			if (\Model::match($eachModel, $data)) {
				return true;
			}
		}
		return false;
	}

	public function save() {
		throw new \Exception('TBI');
	}

	/**
	 * I am protected so you don't accidentally call.
	 * 		if needed, open it by defining method delete() in a \Collection class
	 */
	protected function _delete() {
		throw new \Exception('TBI');
	}

	/**
	 * I load objects by preset filters
	 * @param int $skip
	 * @param int $limit
	 * @return $this|null
	 */
	public function loadByFilters($ModelFinderConstraints=null) {
		$filterData = $this->_filters;
		if (empty($filterData)) {
			return null;
		}
		if (count($filterData) == 1) {
			$filterData = reset($filterData);
		}
		else {
			$filterData = array(
				'$or' => $filterData,
			);
		}
		return $this->loadBy($filterData, $ModelFinderConstraints);
	}

	/**
	 * add a filter for loading. Filters are OR'ed so to add an 'AND' expression, pass it in one filter
	 * @param array|null $filter a mongodb compatible filter array or send null to clear all filters
	 * @throws \Exception
	 */
	public function filter($filter) {
		if (is_null($filter)) {
			$this->_filters = array();
		}
		elseif (is_array($filter)) {
			// note I don't even do syntax check. use at own risk
			$this->_filters[] = $filter;
		}
		elseif ($filter instanceof \Model) {
			throw new \Exception('TBI');
		}
	}

	/**
	 * I load by data directly (data can be a mongo query document in array)
	 * @param $loadData
	 * @param \ModelFinderConstraints $ModelFinderConstraints
	 * @return $this
	 */
	public function loadBy($loadData, $ModelFinderConstraints=null) {
		$fields = $ModelFinderConstraints instanceof \ModelFinderConstraints
			? $ModelFinderConstraints->fields
			: [];
		if (!empty($fields)) {
			if (!in_array('_type', $fields)) {
				array_unshift($fields, '_type');
			}
		}
		$DbCollection = static::_getDbCollection();
		$Cursor = $DbCollection->find($loadData, $fields);
		// I might not want to overwrite data if not found... to be checked later
		if (is_null($Cursor)) {
			// do nothing for now, see comment
			throw new \Exception('TBI');
		}
		else {
			if ($ModelFinderConstraints instanceof \ModelFinderConstraints) {
				if (!empty($ModelFinderConstraints->sortFields)) {
					$Cursor->sort($ModelFinderConstraints->sortFields);
				}
				if ($ModelFinderConstraints->start) {
					$Cursor->skip($ModelFinderConstraints->start);
				}
				if ($ModelFinderConstraints->limit) {
					$Cursor->limit($ModelFinderConstraints->limit);
				}
			}
			$this->_matchedCount = $Cursor->count(false);
			$this->_pagesCount = $Cursor->count(true);
			$data = iterator_to_array($Cursor);
			$this->apply($data, false);
		}
		return $this;
	}

	////////////////////////////////////////////////////////////////////////////////
	// data
	////////////////////////////////////////////////////////////////////////////////

	public function at($key) {
		if (!is_scalar($key)) {
			throw new \Exception();
		}
		if ($key === -1) {
			$keys = array_slice(array_keys($this->_data), -1);
			$key = reset($keys);
		}
		if (isset($this->_data[$key])) {
			$data = $this->_data[$key];
			if (is_array($data)) {
				$classname = $this->_getModelClassname();
				if (isset($data['_type'])) {
					$classname = $data['_type'];
				}
				if (strpos($classname, 'Abstract') !== false) {
					return null;
				}
				$Model = new $classname($data, true);
				$this->_data[$key] = $Model;
			}
			return $this->_data[$key];
		}
		else return null;
	}

	/**
	 * I return data of all members
	 *
	 * @param bool $whichData just to pass by to children
	 * @return array
	 */
	public function getData($whichData = \ModelManager::DATA_ALL, $asIs=true) {
		$data = array();
		foreach ($this->_data as $eachKey=>$eachVal) {
			$data[$eachKey] = $eachVal instanceof \Model
				? $eachVal->Data()->getData(true, $whichData, $asIs)
				: $eachVal;
		}
		return $data;
	}

	/**
	 * I return count() like $Cursor would have had
	 * @param bool $all
	 * @return int
	 */
	public function getCount($all=false) {
		return $all ? $this->_matchedCount : $this->_pagesCount;
	}

	////////////////////////////////////////////////////////////////////////////////
	// arrayaccess
	////////////////////////////////////////////////////////////////////////////////

	public function offsetExists($key) {
		return array_key_exists($key, $this->_data);
	}

	public function offsetGet($key) {
		return $this->at($key);
	}

	public function offsetSet($key, $val) {
		return $this->at($key, $val);
	}

	public function offsetUnset($key) {
		unset($this->_data[$key]);
	}

	////////////////////////////////////////////////////////////////////////////////
	// iterator
	////////////////////////////////////////////////////////////////////////////////

	public function current() {
		$key = key($this->_data);
		return $key === null ? null : $this->at($key);
	}

	public function key() {
		return key($this->_data);
	}

	public function next() {
		next($this->_data);
		return $this->current();
	}

	public function rewind() {
		reset($this->_data);
		return $this->current();
	}

	public function valid() {
		return key($this->_data) !== null;
	}

	////////////////////////////////////////////////////////////////////////////////
	// countable
	////////////////////////////////////////////////////////////////////////////////

	public function count() {
		return count($this->_data);
	}
}
