<?php

namespace Maui;

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

	public function getIterator() {
		return new \ArrayIterator($this->_schema);
	}

	public function fields() {
		return array_keys($this->_schema);
	}

	public function hasField($key) {
		return isset($this->_schema[$key]);
	}

	/**
	 * I return SchemaAttr or SchemaRelative object on $key
	 * @param $key
	 * @return \SchemaAttr
	 */
	public function field($key) {
		return $this->_schema[$key];
	}

	public function hasAttr($key) {
		return isset($this->_schema[$key]) && ($this->_schema[$key] instanceof \SchemaAttr);
	}

	public function hasRelative($key) {
		return isset($this->_schema[$key]) && ($this->_schema[$key] instanceof \SchemaRelative);
	}

	/**
	 * @param $key
	 * @return \SchemaValidator[]
	 */
	public function validators($key) {
		return $this->_schema[$key]->validators();
	}


}
