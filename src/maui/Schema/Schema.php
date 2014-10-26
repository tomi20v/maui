<?php

namespace maui;

/**
 * Class Schema
 * @todo add some control of ordering fields
 *
 * @package maui
 */
class Schema implements \IteratorAggregate {

	protected $_schema = array();

	public function __get($key) {
		switch(true) {
			case array_key_exists($key, $this->_schema):
				return $this->_schema[$key];
			default:
				throw new \Exception(echon($key));
		}
	}

	/**
	 * iterator for foreach
	 * @return \ArrayIterator|\Traversable
	 */
	public function getIterator() {
		return new \ArrayIterator($this->_schema);
	}

	/**
	 * I return all fields
	 * @return array
	 */
	public function fields() {
		return array_keys($this->_schema);
	}

	/**
	 * I return true if schema has field named $key
	 * @param $key
	 * @return bool
	 */
	public function hasField($key) {
		return isset($this->_schema[$key]);
	}

	/**
	 * I return SchemaAttr or SchemaRelative object on $key
	 *
*@param $key
	 * @return \SchemaFieldAttr
	 */
	public function field($key) {
		return $this->_schema[$key];
	}

	/**
	 * I return true if $key field exists and is an attribute
	 * @param $key
	 * @return bool
	 */
	public function hasAttr($key) {
		return isset($this->_schema[$key]) && ($this->_schema[$key] instanceof \SchemaFieldAttr);
	}

	/**
	 * I return an attr. Identical to field($key) but has better IDE support...
	 *
*@param string $key
	 * @return \SchemaFieldAttr
	 */
	public function getAttr($key) {
		return $this->_schema[$key];
	}

	/**
	 * I return true if $key field exists and is a relative
	 * @param $key
	 * @return bool
	 */
	public function hasRelative($key) {
		return isset($this->_schema[$key]) && ($this->_schema[$key] instanceof \SchemaFieldRelative);
	}

	/**
	 * I return a relative. Identical to field($key) but has better IDE support...
	 *
*@param string $key
	 * @return \SchemaFieldRelative
	 */
	public function getRelative($key) {
		return $this->_schema[$key];
	}

	/**
	 * I return all field names which are relatives
	 * @return string[] field names
	 */
	public function relatives() {
		$relatives = array();
		foreach ($this->_schema as $eachKey=>$eachVal) {
			if ($this->hasRelative($eachKey)) {
				$relatives[] = $eachKey;
			}
		}
		return $relatives;
	}

	/**
	 * I return validators for a field
	 * @param $key
	 * @return \SchemaValidator[]
	 */
	public function validators($key) {
		return $this->_schema[$key]->validators();
	}


}
