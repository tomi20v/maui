<?php

namespace Maui;

class Collection implements \Arrayaccess, \Iterator {

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

	/**
	 * I return name of collection in DB
	 * @return string
	 * @extendMe - reuse another DB collection by returning its name from here
	 */
	public static function getDbCollectionName() {
		$collectionName = get_called_class();
		if ($pos = strrpos($collectionName, '\\')) {
			$collectionName = substr($collectionName, $pos+1);
		}
		return $collectionName;
	}

	public function __construct($data=null, $modelClassname=null) {
		if (!is_null($modelClassname)) {
			$this->_modelClassname = $modelClassname;
		}
		if (!is_null($data)) {
			$this->apply($data, true);
		}
	}

	/**
	 * I return the actual DB collection to use
	 * @return \MongoCollection
	 */
	protected function _getDbCollection() {
		$collectionName = static::getDbCollectionName();
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
	 * @param $ModelOrData
	 * @return bool
	 * @todo FIXME
	 */
	public function contains($ModelOrData) {
		// @todo FIXME
		echop('TODO: implement Collection::contains()');
		return true;
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

	public function filter($modeOrFilter) {
		if (is_null($modeOrFilter)) {
			$this->_filters = array();
		}
		elseif (is_array($modeOrFilter)) {
			// note I don't even do syntax check. use at own risk
			$this->_filters[] = $modeOrFilter;
		}
		elseif ($modeOrFilter instanceof \Model) {
			throw new \Exception('TBI');
		}
	}

	////////////////////////////////////////////////////////////////////////////////
	// data
	////////////////////////////////////////////////////////////////////////////////

	public function at($key) {
		if ($key instanceof \MongoId) {
			$key = $key->id;
		}
		if (!is_scalar($key)) {
			throw new \Exception();
		}
		if (isset($this->_data[$key])) {
			$data = $this->_data[$key];
			$classname = $this->_getModelClassname();
			$this->_data[$key] = new $classname($data, true);
			return $this->_data[$key];
		}
		else return null;
	}

	/**
	 * I return data of all members
	 * @param bool $allOrChanged just to pass by to children
	 * @return array
	 */
	public function getData($allOrChanged = true) {
		$data = array();
		foreach ($this->_data as $eachKey=>$eachVal) {
			$data[$eachKey] = $eachVal instanceof \Model
				? $eachVal->getData($allOrChanged)
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
		return $key === false ? null : $this->at($key);
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
	}

	public function valid() {
		return key($this->_data) !== false;
	}

}
