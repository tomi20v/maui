<?php

namespace Maui;

class Collection {

	protected $_modelClassname;

	/**
	 * @var mixed[] I hold an array of items in collection, either their data only or
	 * 		their instance after they're constructed
	 */
	protected $_data = array();

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

	public function __construct($data=null) {
		if (!is_null($data)) {
			$this->apply($data, true);
		}
	}

	protected function _getDbCollection() {
		$collectionName = static::getDbCollectionName();
		return \Maui::instance()->dbDb()->$collectionName;
	}

	/**
	 * I apply data from array. Can be array of data from DB or array of models
	 * @param array[]|\Model[] $data
	 * @param bool $replace - send true to replace current data (also faster). Otherwise $data is merged
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
			die('fixme');
			//$this->_data = $this->_data + $data;
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
	public function add($data) {
		// @todo support adding collections as well
		if ($data instanceof \Model) {
			$data = array($data);
		}
		elseif ($data instanceof \Collection) {
			throw new \Exception('TBI');
		}
		if (!is_array($data)) {
			throw new \Exception(echon($data));
		}
		return $this->apply($data, false);
	}

	public function remove($ModelOrModels) {
		throw new \Exception('TBI');
	}

	public function contains($ModelOrData) {

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

}
