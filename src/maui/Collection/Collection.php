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
			$collectionName = $modelClassname::getCollectionClassName();
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
			foreach ($data as $eachData) {
				$this->add($eachData);
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
*@param $data
	 * @return $this
	 * @throws \Exception
	 */
	public function add($data, $checkDuplicates=true) {
		// @todo support adding collections as well
		if (is_array($data) || ($data instanceof \Model));
		else {
			throw new \Exception(echon($data) . ' / ' . echon($checkDuplicates));
		}
		if ($checkDuplicates && $this->contains($data));
		else {
			$this->_data[] = $data;
		}
		return $this;
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
			? $ModelOrData->getData(true, \ModelManager::DATA_ALL, true)
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
	public function loadByFilters($skip=0, $limit=0) {
		$DbCollection = $this->_getDbCollection();
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
		$cursor = $DbCollection->find($filterData);
		if ($skip) {
			$cursor->skip($skip);
		}
		if ($limit) {
			$cursor->limit($limit);
		}
		$this->_data = iterator_to_array($cursor);
		return $this;
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

	////////////////////////////////////////////////////////////////////////////////
	// data
	////////////////////////////////////////////////////////////////////////////////

	public function at($key) {
		if (!is_scalar($key)) {
			throw new \Exception();
		}
		if (isset($this->_data[$key])) {
			$data = $this->_data[$key];
			if (is_array($data)) {
				$classname = $this->_getModelClassname();
				if (isset($data['_type'])) {
					$classname = $data['_type'];
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
*@param bool $whichData just to pass by to children
	 * @return array
	 */
	public function getData($whichData = true, $asIs=true) {
		$data = array();
		foreach ($this->_data as $eachKey=>$eachVal) {
			$data[$eachKey] = $eachVal instanceof \Model
				? $eachVal->getData(true, $whichData, $asIs)
				: $eachVal;
		}
		return $data;
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
