<?php

namespace maui;

/**
 * Class ModelFinder - I am like a query builder.
 * Only part of mongo query operators are implemented
 *
 * @package maui
 */
class ModelFinder {

	protected $_classname;

	protected $_criteria = array();

	public function __construct($classname) {
		$this->_classname = $classname;
	}

	protected function _addCriteria($field, $criteria) {
		if (!array_key_exists($field, $this->_criteria)) {
			$this->_criteria[$field] = $criteria;
		}
		else {
			throw new \Exception('unimplemented');
		}
		return $this;
	}

	/**
	 * I clear a field's criteria (if any)
	 * @param $field
	 * @return $this
	 */
	public function clear($field) {
		unset($this->_criteria[$field]);
		return $this;
	}

	/**
	 * I return a Model object found by the criteria
	 * @return \Model
	 */
	public function findOne() {
		$classname = $this->_classname;
		/**
		 * @var \Model $Model
		 */
		$Model = new $classname();
		$Model->loadBy($this->_criteria);
		return $Model;
	}

	/**
	 * I return a collection of matching elements
	 * @param ModelFinderConstraints $ModelFinderConstraints
	 * @return \Collection
	 */
	public function findAll($ModelFinderConstraints=null) {
		$modelClassname = $this->_classname;
		$Collection = $modelClassname::getCollection();
		$Collection->loadBy($this->_criteria, $ModelFinderConstraints);
		return $Collection;
	}

	////////////////////////////////////////////////////////////////////////////////
	// comparison
	////////////////////////////////////////////////////////////////////////////////

	public function equals($field, $val) {
		return $this->_addCriteria($field, $val);
	}

	public function notEquals($field, $val) {
		return $this->_addCriteria($field, array('$ne' => $val));
	}

	public function greaterThan($field, $val) {
		return $this->_addCriteria($field, array('$gt' => $val));
	}

	public function greaterThanOrEquals($field, $val) {
		return $this->_addCriteria($field, array('$gte' => $val));
	}

	public function lessThan($field, $val) {
		return $this->_addCriteria($field, array('$lt' => $val));
	}

	public function lessThanOrEquals($field, $val) {
		return $this->_addCriteria($field, array('$lte' => $val));
	}

	public function in($field, $val) {
		return $this->_addCriteria($field, array('$in' => $val));
	}

	public function notIn($field, $val) {
		return $this->_addCriteria($field, array('$nin' => $val));
	}

	////////////////////////////////////////////////////////////////////////////////
	// evaluation
	////////////////////////////////////////////////////////////////////////////////

	public function mod($field, $val) {
		return $this->_addCriteria($field, array('$mod' => $val));
	}

	public function regex($field, $pattern) {
		return $this->_addCriteria($field, new \MongoRegex($pattern));
	}

	////////////////////////////////////////////////////////////////////////////////
	// element
	////////////////////////////////////////////////////////////////////////////////

	public function exists($field, $doesExist=true) {
		return $this->_addCriteria($field, array('$exists' => $doesExist));
	}

	public function notNull($field) {
		return $this->_addCriteria($field, array('$exists' => true, '$ne' => null));
	}

	public function doesNotExist($field) {
		return $this->exists($field, false);
	}

	////////////////////////////////////////////////////////////////////////////////
	// element
	////////////////////////////////////////////////////////////////////////////////

	public function where($where) {
		return $this->_addCriteria('$where', $where);
	}

}
