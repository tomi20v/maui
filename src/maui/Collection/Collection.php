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

	public static function getCollectionName() {
		$collectionName = get_called_class();
		if ($pos = strrpos($collectionName, '\\')) {
			$collectionName = substr($collectionName, $pos+1);
		}
		return $collectionName;
	}

	public function __construct($data=null) {

	}

	public function clear() {
		throw new \Exception('TBI');
	}

	public function add($ModelOrModels) {
		throw new \Exception('TBI');
	}

	public function remove($ModelOrModels) {
		throw new \Exception('TBI');
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

	public function loadByFilters() {
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
//		echop($filterData); die;
		$cursor = $DbCollection->find($filterData);
		echop('count: ' . $cursor->count());
		echop($cursor);
		die('HOKI');
		throw new \Exception('TBI');
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

	protected function _getDbCollection() {
		$collectionName = static::getCollectionName();
		return \Maui::instance()->dbDb()->$collectionName;
	}

}
